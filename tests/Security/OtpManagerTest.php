<?php
declare(strict_types=1);

namespace Boyd\LoginLibrary\Tests\Security;

use Boyd\LoginLibrary\Repositories\AttemptRepository;
use Boyd\LoginLibrary\Security\IpAttemptThrottle;
use Boyd\LoginLibrary\Security\OtpChallengeOutcome;
use Boyd\LoginLibrary\Security\OtpManager;
use Boyd\LoginLibrary\Security\OtpVerificationOutcome;
use Boyd\LoginLibrary\Tests\Support\InMemoryOtpRepository;
use Boyd\LoginLibrary\Tests\Support\RecordingAuditLogger;
use Boyd\LoginLibrary\Tests\Support\SpyOtpMailer;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class OtpManagerTest extends TestCase
{
    private const KEY = 'test-only-hmac-key-0123456789abcdef';

    private InMemoryOtpRepository $repo;
    private SpyOtpMailer $mailer;
    private RecordingAuditLogger $audit;

    protected function setUp(): void
    {
        $this->repo = new InMemoryOtpRepository();
        $this->mailer = new SpyOtpMailer();
        $this->audit = new RecordingAuditLogger();
    }

    private function manager(
        int $codeLength = 6,
        int $ttl = 600,
        int $maxAttempts = 5,
        int $resendCooldown = 60,
        int $maxSendsPerHour = 5
    ): OtpManager {
        return new OtpManager(
            $this->repo,
            $this->mailer,
            self::KEY,
            $this->audit,
            $codeLength,
            $ttl,
            $maxAttempts,
            $resendCooldown,
            $maxSendsPerHour
        );
    }

    public function test_start_challenge_issues_and_emails_a_code(): void
    {
        $outcome = $this->manager()->startChallenge('ref@example.com');

        $this->assertSame(OtpChallengeOutcome::Issued, $outcome);
        $this->assertSame(1, $this->mailer->count());
        $this->assertSame('ref@example.com', $this->mailer->sent[0]['email']);
        $this->assertSame(600, $this->mailer->sent[0]['ttl']);
        $this->assertMatchesRegularExpression('/^\d{6}$/', $this->mailer->sent[0]['code']);
        $this->assertSame(1, $this->repo->activeCountFor('ref@example.com'));
    }

    public function test_stored_value_is_the_keyed_hmac_of_the_emailed_code(): void
    {
        $this->manager()->startChallenge('ref@example.com');
        $row = $this->repo->findLatestActive('ref@example.com');

        $this->assertNotNull($row);
        $this->assertSame(
            hash_hmac('sha256', "ref@example.com\0" . $this->mailer->lastCode(), self::KEY),
            $row['code_hash']
        );
    }

    public function test_identifier_is_normalised(): void
    {
        $this->manager()->startChallenge('  Ref@Example.COM ');

        $this->assertSame(1, $this->repo->activeCountFor('ref@example.com'));
        $this->assertSame(
            OtpVerificationOutcome::Verified,
            $this->manager()->verifyChallenge('REF@example.com', $this->mailer->lastCode())
        );
    }

    public function test_resend_within_cooldown_is_throttled_and_sends_nothing(): void
    {
        $m = $this->manager(resendCooldown: 300);
        $m->startChallenge('ref@example.com');
        $second = $m->startChallenge('ref@example.com');

        $this->assertSame(OtpChallengeOutcome::ResendThrottled, $second);
        $this->assertSame(1, $this->mailer->count());
        $this->assertTrue($this->audit->has('otp_resend_throttled'));
    }

    public function test_hourly_send_cap_is_enforced(): void
    {
        $m = $this->manager(resendCooldown: 0, maxSendsPerHour: 3);

        $outcomes = [
            $m->startChallenge('ref@example.com'),
            $m->startChallenge('ref@example.com'),
            $m->startChallenge('ref@example.com'),
            $m->startChallenge('ref@example.com'),
        ];

        $this->assertSame(
            [OtpChallengeOutcome::Issued, OtpChallengeOutcome::Issued, OtpChallengeOutcome::Issued, OtpChallengeOutcome::RateLimited],
            $outcomes
        );
        $this->assertSame(3, $this->mailer->count());
        $this->assertTrue($this->audit->has('otp_rate_limited'));
    }

    public function test_correct_code_verifies_once_then_cannot_be_replayed(): void
    {
        $m = $this->manager();
        $m->startChallenge('ref@example.com');
        $code = $this->mailer->lastCode();

        $this->assertSame(OtpVerificationOutcome::Verified, $m->verifyChallenge('ref@example.com', $code));
        $this->assertSame(OtpVerificationOutcome::NoActiveChallenge, $m->verifyChallenge('ref@example.com', $code));
        $this->assertTrue($this->audit->has('otp_verified'));
    }

    public function test_incorrect_code_burns_an_attempt(): void
    {
        $m = $this->manager();
        $m->startChallenge('ref@example.com');

        $this->assertSame(OtpVerificationOutcome::IncorrectCode, $m->verifyChallenge('ref@example.com', '000000'));
        $this->assertSame(1, $this->repo->findLatestActive('ref@example.com')['attempts']);
        $this->assertTrue($this->audit->has('otp_failed'));
    }

    public function test_challenge_locks_after_max_attempts_even_with_the_right_code(): void
    {
        $m = $this->manager(maxAttempts: 3);
        $m->startChallenge('ref@example.com');
        $code = $this->mailer->lastCode();
        $wrong = $code === '999999' ? '000000' : '999999';

        $this->assertSame(OtpVerificationOutcome::IncorrectCode, $m->verifyChallenge('ref@example.com', $wrong));
        $this->assertSame(OtpVerificationOutcome::IncorrectCode, $m->verifyChallenge('ref@example.com', $wrong));
        $this->assertSame(OtpVerificationOutcome::IncorrectCode, $m->verifyChallenge('ref@example.com', $wrong));
        $this->assertSame(OtpVerificationOutcome::TooManyAttempts, $m->verifyChallenge('ref@example.com', $code));
        $this->assertTrue($this->audit->has('otp_locked'));
    }

    public function test_verify_with_no_challenge_is_safe(): void
    {
        $this->assertSame(
            OtpVerificationOutcome::NoActiveChallenge,
            $this->manager()->verifyChallenge('stranger@example.com', '123456')
        );
    }

    public function test_expired_challenge_does_not_verify(): void
    {
        $m = $this->manager();
        $m->startChallenge('ref@example.com');
        $code = $this->mailer->lastCode();
        $this->repo->forceExpireAll();

        $this->assertSame(OtpVerificationOutcome::NoActiveChallenge, $m->verifyChallenge('ref@example.com', $code));
    }

    public function test_issuing_a_new_code_supersedes_the_previous_one(): void
    {
        $m = $this->manager(resendCooldown: 0);
        $m->startChallenge('ref@example.com');
        $first = $this->mailer->sent[0]['code'];
        $m->startChallenge('ref@example.com');
        $second = $this->mailer->sent[1]['code'];

        $this->assertNotSame($first, $second);
        // The superseded code no longer authenticates...
        $this->assertNotSame(OtpVerificationOutcome::Verified, $m->verifyChallenge('ref@example.com', $first));
        // ...but the newest one does.
        $this->assertSame(OtpVerificationOutcome::Verified, $this->manager()->verifyChallenge('ref@example.com', $second));
    }

    public function test_code_length_is_configurable(): void
    {
        $this->manager(codeLength: 8)->startChallenge('ref@example.com');
        $this->assertMatchesRegularExpression('/^\d{8}$/', $this->mailer->lastCode());
    }

    public function test_purge_expired_removes_dead_rows(): void
    {
        $m = $this->manager();
        $m->startChallenge('ref@example.com');
        $this->repo->forceExpireAll();

        $m->purgeExpired(0);

        $this->assertSame([], $this->repo->rows);
    }

    public function test_start_challenge_rejects_a_non_email_identifier(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->manager()->startChallenge('+15551234567');
    }

    public function test_start_challenge_rejects_an_empty_identifier(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->manager()->startChallenge('');
    }

    public function test_verify_challenge_rejects_a_non_email_identifier(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->manager()->verifyChallenge('not-an-email', '123456');
    }

    public function test_a_phone_number_never_reaches_the_repository(): void
    {
        // Confirms enforcement happens before any persistence call, not just
        // that an exception is eventually thrown somewhere.
        $repo = $this->createMock(\Boyd\LoginLibrary\Contracts\OtpRepositoryInterface::class);
        $repo->expects($this->never())->method('findLatestActive');
        $repo->expects($this->never())->method('create');

        $m = new OtpManager($repo, $this->mailer, self::KEY, $this->audit);

        try {
            $m->startChallenge('+15551234567');
            $this->fail('Expected InvalidArgumentException was not thrown.');
        } catch (InvalidArgumentException $e) {
            // expected
        }
    }

    public function test_constructor_rejects_out_of_range_code_length(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new OtpManager($this->repo, $this->mailer, self::KEY, null, codeLength: 3);
    }

    public function test_constructor_rejects_code_length_above_nine(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new OtpManager($this->repo, $this->mailer, self::KEY, null, codeLength: 10);
    }

    public function test_constructor_rejects_nonpositive_ttl(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new OtpManager($this->repo, $this->mailer, self::KEY, null, ttlSeconds: 0);
    }

    public function test_constructor_rejects_a_short_hmac_key(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new OtpManager($this->repo, $this->mailer, 'too-short');
    }

    public function test_stored_value_is_bound_to_the_address_it_was_issued_to(): void
    {
        $m = $this->manager();
        $m->startChallenge('alice@example.com');
        $aliceCode = $this->mailer->lastCode();
        $aliceRow = $this->repo->findLatestActive('alice@example.com');

        // Plant Alice's stored value on Bob's challenge: the same code must not
        // verify for Bob, because the address is part of what was signed.
        $m->startChallenge('bob@example.com');
        $bobRow = $this->repo->findLatestActive('bob@example.com');
        $this->repo->rows[$bobRow['id']]['code_hash'] = $aliceRow['code_hash'];

        $this->assertSame(OtpVerificationOutcome::IncorrectCode, $m->verifyChallenge('bob@example.com', $aliceCode));
    }

    public function test_a_different_key_cannot_verify_a_stored_code(): void
    {
        $this->manager()->startChallenge('ref@example.com');
        $code = $this->mailer->lastCode();

        $otherKeyManager = new OtpManager($this->repo, $this->mailer, 'a-completely-different-secret-key-0000');

        $this->assertSame(OtpVerificationOutcome::IncorrectCode, $otherKeyManager->verifyChallenge('ref@example.com', $code));
    }

    public function test_a_leading_zero_code_is_not_confused_with_its_numeric_value(): void
    {
        // "000123" == "123" under PHP's loose numeric-string comparison; the
        // digest comparison must be exact.
        $m = $this->manager();
        $m->startChallenge('ref@example.com');
        $row = $this->repo->findLatestActive('ref@example.com');
        $this->repo->rows[$row['id']]['code_hash'] = hash_hmac('sha256', "ref@example.com\0" . '000123', self::KEY);

        $this->assertSame(OtpVerificationOutcome::IncorrectCode, $m->verifyChallenge('ref@example.com', '123'));
        $this->assertSame(OtpVerificationOutcome::Verified, $m->verifyChallenge('ref@example.com', '000123'));
    }

    public function test_works_without_an_audit_logger(): void
    {
        $m = new OtpManager($this->repo, $this->mailer, self::KEY, null);
        $m->startChallenge('ref@example.com');

        $this->assertSame(
            OtpVerificationOutcome::Verified,
            $m->verifyChallenge('ref@example.com', $this->mailer->lastCode())
        );
    }

    /** Real IpAttemptThrottle (final, can't be mocked) backed by a mocked AttemptRepository. */
    private function lockedOutThrottle(): IpAttemptThrottle
    {
        /** @var AttemptRepository&\PHPUnit\Framework\MockObject\MockObject $repo */
        $repo = $this->createMock(AttemptRepository::class);
        $repo->method('countRecentAttempts')->willReturn(999);
        return new IpAttemptThrottle($repo, maxAttempts: 5, lockoutSeconds: 900);
    }

    private function openThrottle(): IpAttemptThrottle
    {
        /** @var AttemptRepository&\PHPUnit\Framework\MockObject\MockObject $repo */
        $repo = $this->createMock(AttemptRepository::class);
        $repo->method('countRecentAttempts')->willReturn(0);
        return new IpAttemptThrottle($repo, maxAttempts: 5, lockoutSeconds: 900);
    }

    public function test_start_challenge_refuses_a_locked_out_ip_and_sends_nothing(): void
    {
        $m = new OtpManager($this->repo, $this->mailer, self::KEY, $this->audit, ipThrottle: $this->lockedOutThrottle());

        $outcome = $m->startChallenge('ref@example.com', '9.9.9.9');

        $this->assertSame(OtpChallengeOutcome::IpLockedOut, $outcome);
        $this->assertSame(0, $this->mailer->count());
        $this->assertTrue($this->audit->has('otp_ip_locked'));
    }

    public function test_verify_challenge_refuses_a_locked_out_ip_before_checking_the_code(): void
    {
        $open = new OtpManager($this->repo, $this->mailer, self::KEY, $this->audit, ipThrottle: $this->openThrottle());
        $open->startChallenge('ref@example.com');
        $code = $this->mailer->lastCode();

        $locked = new OtpManager($this->repo, $this->mailer, self::KEY, $this->audit, ipThrottle: $this->lockedOutThrottle());
        $outcome = $locked->verifyChallenge('ref@example.com', $code, '9.9.9.9');

        $this->assertSame(OtpVerificationOutcome::IpLockedOut, $outcome);
        $this->assertTrue($outcome->isLockedOut());
    }

    public function test_incorrect_code_records_a_failure_against_the_ip_throttle(): void
    {
        /** @var AttemptRepository&\PHPUnit\Framework\MockObject\MockObject $repo */
        $repo = $this->createMock(AttemptRepository::class);
        $repo->method('countRecentAttempts')->willReturn(0);
        $repo->expects($this->once())->method('recordAttempt')->with('9.9.9.9', 'ref@example.com');

        $m = new OtpManager($this->repo, $this->mailer, self::KEY, $this->audit, ipThrottle: new IpAttemptThrottle($repo, 5, 900));
        $m->startChallenge('ref@example.com');

        $m->verifyChallenge('ref@example.com', '000000', '9.9.9.9');
    }

    public function test_verified_code_clears_the_ip_throttle(): void
    {
        /** @var AttemptRepository&\PHPUnit\Framework\MockObject\MockObject $repo */
        $repo = $this->createMock(AttemptRepository::class);
        $repo->method('countRecentAttempts')->willReturn(0);
        $repo->expects($this->once())->method('clearAttempts')->with('9.9.9.9');
        $repo->expects($this->never())->method('recordAttempt');

        $m = new OtpManager($this->repo, $this->mailer, self::KEY, $this->audit, ipThrottle: new IpAttemptThrottle($repo, 5, 900));
        $m->startChallenge('ref@example.com');
        $code = $this->mailer->lastCode();

        $this->assertSame(OtpVerificationOutcome::Verified, $m->verifyChallenge('ref@example.com', $code, '9.9.9.9'));
    }

    public function test_no_ip_throttle_behaves_exactly_as_before(): void
    {
        $m = new OtpManager($this->repo, $this->mailer, self::KEY, $this->audit);
        $m->startChallenge('ref@example.com');

        $this->assertSame(
            OtpVerificationOutcome::Verified,
            $m->verifyChallenge('ref@example.com', $this->mailer->lastCode(), '9.9.9.9')
        );
    }

    public function test_losing_the_consume_race_does_not_report_verified(): void
    {
        // Repository whose consume() always reports "someone else got there
        // first" -- the manager must not treat that as a successful login.
        $racyRepo = new class extends InMemoryOtpRepository {
            public function consume(int $id): bool
            {
                return false;
            }
        };

        $m = new OtpManager($racyRepo, $this->mailer, self::KEY, $this->audit);
        $m->startChallenge('ref@example.com');

        $this->assertSame(
            OtpVerificationOutcome::NoActiveChallenge,
            $m->verifyChallenge('ref@example.com', $this->mailer->lastCode())
        );
        $this->assertFalse($this->audit->has('otp_verified'));
    }
}
