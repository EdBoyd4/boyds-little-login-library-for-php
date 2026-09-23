<?php
declare(strict_types=1);

namespace Boyd\LoginLibrary\Security;

use Boyd\LoginLibrary\Models\User;
use Boyd\LoginLibrary\Repositories\UserRepository;
use Exception;

/**
 * Proves a username + password credential. Structural peer of OtpManager:
 * each owns exactly one "is this identity who they claim to be" decision, and
 * hands AuthManager an already-proven User -- AuthManager itself never
 * touches a credential.
 *
 * Deliberately does NOT check IpAttemptThrottle::isLockedOut() itself, unlike
 * OtpManager -- that stays in AuthManager::login(), checked before this class
 * is ever reached. Reordering it here would put AuthManager's near-unreachable
 * checkNoActiveSession() guard ahead of the lockout gate (LoginController
 * already short-circuits an existing session before AuthManager::login() is
 * ever called, so that guard essentially never fires in practice -- confirmed
 * against this project's own audit log: zero 'discrepancy' events ever, across
 * a great deal of manual testing, versus real lockouts on the sibling OTP
 * throttle). This class still records outcomes against the same throttle
 * (recordFailure() / clear()) so the shared login_attempts table stays
 * accurate; it just isn't the one deciding whether to even attempt.
 */
class PasswordAuthenticator
{
    public function __construct(
        private UserRepository $userRepository,
        private ?IpAttemptThrottle $ipThrottle = null
    ) {}

    /**
     * @throws Exception If the credential is wrong or the account doesn't exist
     *         -- same generic message either way, so a response can't be used
     *         to enumerate valid usernames.
     */
    public function verify(string $username, string $password, string $ipAddress = '0.0.0.0'): User
    {
        $user = $this->userRepository->findByUsername($username);

        if (!$user || !password_verify($password, $user->getPasswordHash())) {
            // Never log the submitted password -- error_log persists longer and is
            // read by more tooling than the database itself.
            error_log(sprintf(
                "Login failed for username '%s' from IP %s (user exists: %s)",
                $username, $ipAddress, $user ? 'yes' : 'no'
            ));
            $this->ipThrottle?->recordFailure($ipAddress, $username);
            throw new Exception("Invalid username or password.");
        }

        $this->ipThrottle?->clear($ipAddress);

        return $user;
    }
}
