<?php
declare(strict_types=1);

namespace Boyd\LoginLibrary\Tests\Security;

use Boyd\LoginLibrary\Security\OtpChallengeOutcome;
use Boyd\LoginLibrary\Security\OtpVerificationOutcome;
use PHPUnit\Framework\TestCase;

final class OtpOutcomeTest extends TestCase
{
    public function test_only_issued_counts_as_sent(): void
    {
        $this->assertTrue(OtpChallengeOutcome::Issued->codeWasSent());
        $this->assertFalse(OtpChallengeOutcome::ResendThrottled->codeWasSent());
        $this->assertFalse(OtpChallengeOutcome::RateLimited->codeWasSent());
    }

    public function test_only_verified_is_verified(): void
    {
        $this->assertTrue(OtpVerificationOutcome::Verified->isVerified());
        $this->assertFalse(OtpVerificationOutcome::IncorrectCode->isVerified());
        $this->assertFalse(OtpVerificationOutcome::NoActiveChallenge->isVerified());
        $this->assertFalse(OtpVerificationOutcome::TooManyAttempts->isVerified());
    }
}
