<?php
declare(strict_types=1);

namespace Boyd\LoginLibrary\Security;

use Boyd\LoginLibrary\Config\LoginConfig;
use Boyd\LoginLibrary\Repositories\AttemptRepository;

/**
 * IP-based lockout against the shared login_attempts table, usable by every
 * authentication mechanism (password login, one-time-code request/verify,
 * ...) so an attacker can't dodge the limit by switching mechanisms from the
 * same IP -- hammering a hundred different email addresses' OTP flows is
 * throttled by the same counter as a hundred failed password guesses.
 *
 * Deliberately just three small methods over AttemptRepository's existing
 * table, not a new one: check/record/clear is the same three-step shape
 * AuthManager::login() already used inline before this was extracted, now
 * reused by OtpManager too instead of being duplicated a second time.
 */
final class IpAttemptThrottle
{
    public function __construct(
        private AttemptRepository $repository,
        private int $maxAttempts,
        private int $lockoutSeconds
    ) {}

    /** Pulls its thresholds from the same LoginConfig knobs password login uses. */
    public static function fromConfig(AttemptRepository $repository, LoginConfig $config): self
    {
        return new self($repository, $config->getMaxLoginAttempts(), $config->getLockoutTimeSeconds());
    }

    public function isLockedOut(string $ipAddress): bool
    {
        return $this->repository->countRecentAttempts($ipAddress, $this->lockoutSeconds) >= $this->maxAttempts;
    }

    /** $identifier is whatever was being attempted (username, email, ...) -- stored alongside the IP for review, not used in the lockout check itself (that's IP-only, matching AttemptRepository::countRecentAttempts). */
    public function recordFailure(string $ipAddress, string $identifier): void
    {
        $this->repository->recordAttempt($ipAddress, $identifier);
    }

    public function clear(string $ipAddress): void
    {
        $this->repository->clearAttempts($ipAddress);
    }
}
