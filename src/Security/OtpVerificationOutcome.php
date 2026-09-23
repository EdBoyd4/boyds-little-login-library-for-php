<?php
declare(strict_types=1);

namespace Boyd\LoginLibrary\Security;

/**
 * Result of OtpManager::verifyChallenge().
 *
 * A caller that cares about user experience can distinguish a lockout
 * (TooManyAttempts: this challenge's own attempt ceiling; IpLockedOut: the
 * requesting IP, see IpAttemptThrottle) from IncorrectCode ("try again"), but
 * should treat IncorrectCode and NoActiveChallenge identically in any message
 * shown to the user so an attacker cannot learn whether a challenge is
 * outstanding. Callers that only care about "should I show a lockout
 * message" can use isLockedOut() rather than checking both cases themselves.
 */
enum OtpVerificationOutcome
{
    case Verified;
    case IncorrectCode;
    case NoActiveChallenge;
    case TooManyAttempts;
    case IpLockedOut;

    public function isVerified(): bool
    {
        return $this === self::Verified;
    }

    public function isLockedOut(): bool
    {
        return $this === self::TooManyAttempts || $this === self::IpLockedOut;
    }
}
