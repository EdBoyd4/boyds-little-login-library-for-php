<?php
declare(strict_types=1);

namespace Boyd\LoginLibrary\Security;

use Boyd\LoginLibrary\Config\LoginConfig;
use Boyd\LoginLibrary\Contracts\OtpMailerInterface;
use Boyd\LoginLibrary\Contracts\OtpRepositoryInterface;
use Boyd\LoginLibrary\Contracts\SecurityAuditLoggerInterface;
use InvalidArgumentException;

/**
 * Issues and verifies short-lived numeric login codes delivered by email.
 *
 * Email only, by policy -- not a generic identifier. normalize() enforces
 * this on every call (throws on anything that doesn't pass
 * FILTER_VALIDATE_EMAIL), regardless of what the caller already checked, so
 * the constraint holds even for a future or alternate caller that skips its
 * own validation. A phone number or any other identifier type is explicitly
 * out of scope for this class: a single normalize() cannot be simultaneously
 * correct for multiple identifier formats (E.164 for phone, case-folding for
 * email, ...), and mixing formats under one under-normalized key is exactly
 * how rate limits and the one-active-challenge guarantee get silently
 * bypassed -- textually different, logically equivalent identifiers (e.g.
 * "+15551234567" vs "(555) 123-4567") would each get their own budget. If
 * phone (or anything else) is ever needed, it belongs in its own module with
 * its own purpose-built normalizer, not grafted onto this one.
 *
 * Design notes:
 *  - The manager knows nothing about users or sessions. The caller decides
 *    whether an address is eligible before startChallenge(), and turns a
 *    Verified outcome into a session via
 *    AuthManager::establishPasswordlessSession().
 *  - Only an HMAC-SHA256 of each code is stored, keyed with a secret that
 *    lives outside the database and bound to the address, so a database-only
 *    leak (SQL injection, a backup, a sibling account on shared hosting)
 *    cannot reveal or forge a live code; plaintext exists just long enough
 *    to hand to the mailer. A plain password hash would add little here: a
 *    6-digit code has only 10^6 possibilities, so it falls to offline
 *    brute force in minutes -- the secret key is what protects it. Rotating
 *    the key only invalidates codes issued in the last few minutes.
 *  - One live code per identifier: issuing supersedes any previous active code.
 *  - Abuse controls: a per-code attempt ceiling, a resend cooldown, a
 *    per-identifier hourly cap on how many codes may be sent, and -- given an
 *    IpAttemptThrottle -- an IP-wide lockout shared with password login, so
 *    one IP can't dodge the per-identifier limits by spreading requests
 *    across many different addresses.
 */
class OtpManager
{
    public function __construct(
        private OtpRepositoryInterface $repository,
        private OtpMailerInterface $mailer,
        private string $hmacKey,
        private ?SecurityAuditLoggerInterface $auditLogger = null,
        private int $codeLength = 6,
        private int $ttlSeconds = 600,
        private int $maxAttempts = 5,
        private int $resendCooldownSeconds = 60,
        private int $maxSendsPerHour = 5,
        private ?IpAttemptThrottle $ipThrottle = null
    ) {
        if (strlen($hmacKey) < 32) {
            throw new InvalidArgumentException('OTP HMAC key must be at least 32 characters (e.g. bin2hex(random_bytes(32))).');
        }
        if ($codeLength < 4 || $codeLength > 9) {
            throw new InvalidArgumentException('OTP code length must be between 4 and 9 digits.');
        }
        foreach (['ttlSeconds' => $ttlSeconds, 'maxAttempts' => $maxAttempts, 'maxSendsPerHour' => $maxSendsPerHour] as $name => $value) {
            if ($value < 1) {
                throw new InvalidArgumentException("OTP {$name} must be a positive integer.");
            }
        }
        if ($resendCooldownSeconds < 0) {
            throw new InvalidArgumentException('OTP resend cooldown must not be negative.');
        }
    }

    /**
     * Convenience constructor pulling every knob from LoginConfig, matching how
     * the rest of the library is wired. $ipThrottle is optional -- omit it and
     * this behaves exactly as before (per-identifier throttling only).
     */
    public static function fromConfig(
        OtpRepositoryInterface $repository,
        OtpMailerInterface $mailer,
        LoginConfig $config,
        ?SecurityAuditLoggerInterface $auditLogger = null,
        ?IpAttemptThrottle $ipThrottle = null
    ): self {
        return new self(
            $repository,
            $mailer,
            $config->getOtpHmacKey(),
            $auditLogger,
            $config->getOtpCodeLength(),
            $config->getOtpTtlSeconds(),
            $config->getOtpMaxAttempts(),
            $config->getOtpResendCooldownSeconds(),
            $config->getOtpMaxSendsPerHour(),
            $ipThrottle
        );
    }

    /**
     * Generate a code, persist its hash, and email the plaintext. The caller
     * should show the user the same neutral message for every return value.
     */
    public function startChallenge(string $email, string $ipAddress = '0.0.0.0'): OtpChallengeOutcome
    {
        $identifier = $this->normalize($email);

        if ($this->ipThrottle !== null && $this->ipThrottle->isLockedOut($ipAddress)) {
            $this->audit('otp_ip_locked', $ipAddress, $email, 'Requesting IP is locked out');
            return OtpChallengeOutcome::IpLockedOut;
        }

        $existing = $this->repository->findLatestActive($identifier);
        if ($existing !== null
            && (time() - $existing['created_at']) < $this->resendCooldownSeconds) {
            $this->audit('otp_resend_throttled', $ipAddress, $email, 'Code re-requested inside the resend cooldown');
            return OtpChallengeOutcome::ResendThrottled;
        }

        if ($this->repository->countRecentFor($identifier, time() - 3600) >= $this->maxSendsPerHour) {
            $this->audit('otp_rate_limited', $ipAddress, $email, 'Hourly one-time code send cap reached');
            return OtpChallengeOutcome::RateLimited;
        }

        $code = $this->generateCode();

        $this->repository->supersedeActiveFor($identifier);
        $this->repository->create(
            $identifier,
            $this->codeDigest($identifier, $code),
            time() + $this->ttlSeconds
        );

        $this->mailer->sendOtp($email, $code, $this->ttlSeconds);
        $this->audit('otp_issued', $ipAddress, $email, 'One-time login code issued');

        return OtpChallengeOutcome::Issued;
    }

    /**
     * Check a code against the live challenge for the address. A correct code is
     * consumed (single use); a wrong code burns one attempt.
     */
    public function verifyChallenge(string $email, string $code, string $ipAddress = '0.0.0.0'): OtpVerificationOutcome
    {
        $identifier = $this->normalize($email);
        $code = trim($code);

        if ($this->ipThrottle !== null && $this->ipThrottle->isLockedOut($ipAddress)) {
            $this->audit('otp_ip_locked', $ipAddress, $email, 'Requesting IP is locked out');
            return OtpVerificationOutcome::IpLockedOut;
        }

        $row = $this->repository->findLatestActive($identifier);

        if ($row === null) {
            return OtpVerificationOutcome::NoActiveChallenge;
        }

        if ($row['attempts'] >= $this->maxAttempts) {
            $this->audit('otp_locked', $ipAddress, $email, 'Too many incorrect one-time code attempts');
            return OtpVerificationOutcome::TooManyAttempts;
        }

        if ($code === '' || !hash_equals($row['code_hash'], $this->codeDigest($identifier, $code))) {
            $this->repository->incrementAttempts($row['id']);
            $this->ipThrottle?->recordFailure($ipAddress, $email);
            $this->audit('otp_failed', $ipAddress, $email, 'Incorrect one-time code');
            return OtpVerificationOutcome::IncorrectCode;
        }

        if (!$this->repository->consume($row['id'])) {
            // A concurrent request already consumed this challenge.
            return OtpVerificationOutcome::NoActiveChallenge;
        }

        $this->ipThrottle?->clear($ipAddress);
        $this->audit('otp_verified', $ipAddress, $email, 'One-time code verified');
        return OtpVerificationOutcome::Verified;
    }

    /**
     * Housekeeping. Safe to call from a cron alongside
     * AttemptRepository::cleanupOldAttempts().
     */
    public function purgeExpired(int $graceSeconds = 86400): void
    {
        $this->repository->cleanupExpired(time() - max(0, $graceSeconds));
    }

    /**
     * @throws InvalidArgumentException if $email isn't a valid email address --
     * this class handles email identifiers only, enforced here regardless of
     * whatever validation the caller already did (see the class docblock).
     */
    private function normalize(string $email): string
    {
        $email = strtolower(trim($email));
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new InvalidArgumentException('OtpManager only accepts email addresses.');
        }
        return $email;
    }

    /** HMAC-SHA256 over "address NUL code", so a digest is only valid for the address it was issued to. */
    private function codeDigest(string $identifier, string $code): string
    {
        return hash_hmac('sha256', $identifier . "\0" . $code, $this->hmacKey);
    }

    private function generateCode(): string
    {
        $max = (10 ** $this->codeLength) - 1;
        return str_pad((string) random_int(0, $max), $this->codeLength, '0', STR_PAD_LEFT);
    }

    private function audit(string $event, string $ipAddress, string $email, ?string $details = null): void
    {
        $this->auditLogger?->logEvent($event, $ipAddress, $email, $details);
    }
}
