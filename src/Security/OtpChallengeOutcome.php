<?php
declare(strict_types=1);

namespace Boyd\LoginLibrary\Security;

/**
 * Result of OtpManager::startChallenge(). The caller should show the user the
 * SAME neutral message regardless of value -- the distinction exists for
 * auditing and for deciding whether a code was actually sent.
 */
enum OtpChallengeOutcome
{
    /** A fresh code was generated, stored, and handed to the mailer. */
    case Issued;

    /** A code was requested again within the resend-cooldown window; ignored. */
    case ResendThrottled;

    /** The per-identifier hourly send cap was reached; no code sent. */
    case RateLimited;

    /** The requesting IP is locked out (see IpAttemptThrottle); no code sent. */
    case IpLockedOut;

    public function codeWasSent(): bool
    {
        return $this === self::Issued;
    }
}
