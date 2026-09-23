<?php
declare(strict_types=1);

namespace Boyd\LoginLibrary\Security;

use Boyd\LoginLibrary\Models\User;
use Boyd\LoginLibrary\Repositories\UserRepository;
use Boyd\LoginLibrary\Repositories\AttemptRepository;
use Boyd\LoginLibrary\Repositories\SecurityAuditRepository;
use Boyd\LoginLibrary\Config\LoginConfig;
use Boyd\LoginLibrary\Models\AuthResult;
use Exception;

class AuthManager
{
    private ?IpAttemptThrottle $ipThrottle;
    private ?PasswordAuthenticator $passwordAuthenticator;

    public function __construct(
        private ?UserRepository $userRepository,
        private SessionManager $sessionManager,
        private LoginConfig $config,
        private ?AttemptRepository $attemptRepository = null,
        private ?SecurityAuditRepository $auditRepository = null
    ) {
        // Same check/record/clear shape OtpManager now also uses (via its own
        // IpAttemptThrottle) against the same login_attempts table -- kept as
        // one collaborator instead of two copies of this logic.
        $this->ipThrottle = $attemptRepository !== null
            ? IpAttemptThrottle::fromConfig($attemptRepository, $config)
            : null;

        // Shares this exact throttle instance (not a second one built from the
        // same config) so login()'s early lockout gate and
        // PasswordAuthenticator's recordFailure()/clear() stay perfectly
        // consistent -- see login() and PasswordAuthenticator's own docblock
        // for why the lockout check itself stays here rather than moving in.
        $this->passwordAuthenticator = $userRepository !== null
            ? new PasswordAuthenticator($userRepository, $this->ipThrottle)
            : null;
    }

    public function enforceHttps(bool $isHttps, string $host, string $requestUri): ?AuthResult
    {
        if (!$isHttps) {
            $redirect = 'https://' . $host . $requestUri;
            return new AuthResult(false, null, $redirect);
        }
        return null;
    }

    /**
     * @throws Exception If login fails
     */
    public function login(string $username, string $password, string $ipAddress = '0.0.0.0'): void
    {
        if ($this->passwordAuthenticator === null) {
            throw new \LogicException('This AuthManager has no password user store; it can only establish passwordless sessions.');
        }

        $this->sessionManager->startSession();

        // Checked here, not inside PasswordAuthenticator, and deliberately
        // before checkNoActiveSession() -- see PasswordAuthenticator's
        // docblock for why that order matters in practice.
        if ($this->ipThrottle !== null && $this->ipThrottle->isLockedOut($ipAddress)) {
            if ($this->auditRepository !== null) {
                $this->auditRepository->logEvent('lockout', $ipAddress, $username, 'Too many failed login attempts');
            }
            throw new Exception("Too many failed login attempts. Please try again later.");
        }

        $this->checkNoActiveSession($ipAddress, $username);

        // Credential check + attempt recording delegated to PasswordAuthenticator
        // (throws the same "Invalid username or password." on failure).
        $user = $this->passwordAuthenticator->verify($username, $password, $ipAddress);

        // No timeout stamp: a password session defers to SessionManager's
        // dynamic getEffectiveTimeoutSeconds() at enforcement time, which is
        // what lets an admin change the session length at runtime via
        // SettingsRepository (see SessionSettingsView). Stamping a fixed value
        // here would silently override that setting on every login.
        $this->establishSession($user, null, $ipAddress, 'User successfully logged in');
    }

    /**
     * Establish an authenticated session for a user who has already proven their
     * identity by another means -- specifically a verified one-time email code
     * (see OtpManager / SelfServiceOtpController). No password is involved.
     *
     * Mirrors the session bookkeeping of login() (fixation regen, session
     * variables, audit) but skips the password check and the IP throttle --
     * OtpManager owns that for the OTP path (its own IpAttemptThrottle,
     * checked before issuing a code and cleared on a verified one), the same
     * way this class owns it for the password path.
     *
     * @throws Exception If a session already exists.
     */
    public function establishPasswordlessSession(User $user, string $ipAddress = '0.0.0.0'): void
    {
        $this->sessionManager->startSession();
        $this->checkNoActiveSession($ipAddress, $user->getUsername(), 'Passwordless session start while session variables already exist');

        // Stamped (unlike login()'s password path): a code-login session keeps
        // this fixed, shorter idle timeout regardless of any admin-configured
        // password-session length -- read back by
        // SessionManager::currentSessionTimeoutSeconds(), even where a
        // different SessionManager instance enforces it.
        $this->establishSession($user, $this->config->getOtpSessionTimeoutSeconds(), $ipAddress, 'User authenticated via one-time email code');
    }

    /**
     * The discrepancy guard shared by login() and establishPasswordlessSession():
     * refuses to start a new session over one that already looks authenticated
     * (a double-submit, or a bug in the caller), rather than silently
     * overwriting it.
     *
     * @throws Exception If a session already exists.
     */
    private function checkNoActiveSession(string $ipAddress, string $username, string $auditDetail = 'Login attempt while session variables already exist'): void
    {
        if (
            isset($_SESSION['user_id']) ||
            isset($_SESSION['user_name']) ||
            isset($_SESSION['user_roles']) ||
            isset($_SESSION['login_timestamp'])
        ) {
            if ($this->auditRepository !== null) {
                $this->auditRepository->logEvent('discrepancy', $ipAddress, $username, $auditDetail);
            } else {
                error_log(sprintf("Security Discrepancy: %s for IP %s, username: %s", $auditDetail, $ipAddress, $username));
            }
            throw new Exception("An active session already exists.");
        }
    }

    /**
     * Writes the authenticated session, shared by both login paths: fixation
     * regen, the identity/session variables, and the success audit event.
     * $stampedTimeoutSeconds is null for a password login (defer to
     * SessionManager's dynamic, settings-aware lookup) or a fixed value for a
     * code login (see establishPasswordlessSession()).
     */
    private function establishSession(User $user, ?int $stampedTimeoutSeconds, string $ipAddress, string $auditDetail): void
    {
        // Prevent session fixation
        $this->sessionManager->regenerate();

        $_SESSION['user_id'] = $user->getId();
        $_SESSION['user_name'] = $user->getUsername();
        $_SESSION['user_roles'] = $user->getRoles();
        $_SESSION['user_source'] = $this->config->getIdentitySource();
        if ($stampedTimeoutSeconds !== null) {
            $_SESSION['session_timeout_seconds'] = $stampedTimeoutSeconds;
        }
        $_SESSION['login_timestamp'] = time();

        if ($this->auditRepository !== null) {
            $this->auditRepository->logEvent('login_success', $ipAddress, $user->getUsername(), $auditDetail);
        }
    }

    public function logout(string $ipAddress = '0.0.0.0'): void
    {
        if ($this->auditRepository !== null) {
            $this->sessionManager->startSession();
            $username = $_SESSION['user_name'] ?? null;
            $this->auditRepository->logEvent('logout', $ipAddress, $username, 'User logged out');
        }
        $this->sessionManager->destroySession();
    }

    public function isLoggedIn(): bool
    {
        $this->sessionManager->startSession();
        return isset($_SESSION['user_id']);
    }

    /**
     * The getIdentitySource() value of whichever LoginConfig's AuthManager
     * established the current session, or null if the session predates this
     * field or the consumer never set one. For a consumer running a single
     * identity store this is always null and can be ignored; for one running
     * several (see LoginConfig::getIdentitySource()) it says which store the
     * logged-in user's ID belongs to.
     */
    public function currentUserSource(): ?string
    {
        $this->sessionManager->startSession();
        return $_SESSION['user_source'] ?? null;
    }

    public function enforceLogin(): ?AuthResult
    {
        if (!$this->isLoggedIn()) {
            return new AuthResult(false, null, $this->config->getLoginRoute());
        }
        $this->sessionManager->enforceTimeout();
        return null;
    }

    public function requireRole(string $requiredRole): ?AuthResult
    {
        if ($loginResult = $this->enforceLogin()) {
            return $loginResult;
        }
        $userRoles = $_SESSION['user_roles'] ?? [];
        if (!in_array($requiredRole, $userRoles, true)) {
            return new AuthResult(false, null, $this->config->getUnauthorizedRoute());
        }
        return null;
    }

    public function requireAllRoles(array $requiredRoles): ?AuthResult
    {
        if ($loginResult = $this->enforceLogin()) {
            return $loginResult;
        }
        $userRoles = $_SESSION['user_roles'] ?? [];
        
        foreach ($requiredRoles as $role) {
            if (!in_array($role, $userRoles, true)) {
                return new AuthResult(false, null, $this->config->getUnauthorizedRoute());
            }
        }
        return null;
    }
}
