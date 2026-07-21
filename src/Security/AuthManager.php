<?php
declare(strict_types=1);

namespace Boyd\LoginLibrary\Security;

use Boyd\LoginLibrary\Models\User;
use Boyd\LoginLibrary\Repositories\UserRepository;
use Boyd\LoginLibrary\Repositories\AttemptRepository;
use Boyd\LoginLibrary\Repositories\SecurityAuditRepository;
use Boyd\LoginLibrary\Config\LoginConfig;
use Exception;

class AuthManager
{
    public function __construct(
        private UserRepository $userRepository,
        private SessionManager $sessionManager,
        private LoginConfig $config,
        private ?AttemptRepository $attemptRepository = null,
        private ?SecurityAuditRepository $auditRepository = null
    ) {
    }

    public function enforceHttps(bool $isHttps, string $host, string $requestUri): ?AuthResult
    {
        if (!$isHttps) {
            $redirect = 'https://' . $host . $requestUri;
            return new \Boyd\LoginLibrary\Models\AuthResult(false, null, $redirect);
        }
        return null;
    }

    /**
     * @throws Exception If login fails
     */
    public function login(string $username, string $password, string $ipAddress = '0.0.0.0'): void
    {
        $this->sessionManager->startSession();

        if ($this->attemptRepository !== null) {
            $recentAttempts = $this->attemptRepository->countRecentAttempts($ipAddress, $this->config->getLockoutTimeSeconds());
            if ($recentAttempts >= $this->config->getMaxLoginAttempts()) {
                if ($this->auditRepository !== null) {
                    $this->auditRepository->logEvent('lockout', $ipAddress, $username, 'Too many failed login attempts');
                }
                throw new Exception("Too many failed login attempts. Please try again later.");
            }
        }

        if (
            isset($_SESSION['user_id']) ||
            isset($_SESSION['user_name']) ||
            isset($_SESSION['user_roles']) ||
            isset($_SESSION['login_timestamp'])
        ) {
            if ($this->auditRepository !== null) {
                $this->auditRepository->logEvent('discrepancy', $ipAddress, $username, 'Login attempt while session variables already exist');
            } else {
                error_log(sprintf("Security Discrepancy: Login attempt while session variables already exist for IP %s, target username: %s", $ipAddress, $username));
            }
            throw new Exception("An active session already exists.");
        }

        $user = $this->userRepository->findByUsername($username);

        if (!$user || !password_verify($password, $user->getPasswordHash())) {
            error_log("LOGIN FAILED. USER EXISTS: " . ($user ? 'YES' : 'NO') . " | GIVEN PASS: " . $password);
            if ($this->attemptRepository !== null) {
                $this->attemptRepository->recordAttempt($ipAddress, $username);
            }
            throw new Exception("Invalid username or password.");
        }

        if ($this->attemptRepository !== null) {
            $this->attemptRepository->clearAttempts($ipAddress);
        }

        // Prevent session fixation
        $this->sessionManager->regenerate();

        $_SESSION['user_id'] = $user->getId();
        $_SESSION['user_name'] = $user->getUsername();
        $_SESSION['user_roles'] = $user->getRoles();
        $_SESSION['login_timestamp'] = time();

        if ($this->auditRepository !== null) {
            $this->auditRepository->logEvent('login_success', $ipAddress, $username, 'User successfully logged in');
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

    public function enforceLogin(): ?AuthResult
    {
        if (!$this->isLoggedIn()) {
            return new \Boyd\LoginLibrary\Models\AuthResult(false, null, $this->config->getLoginRoute());
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
            return new \Boyd\LoginLibrary\Models\AuthResult(false, null, $this->config->getUnauthorizedRoute());
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
                return new \Boyd\LoginLibrary\Models\AuthResult(false, null, $this->config->getUnauthorizedRoute());
            }
        }
        return null;
    }
}
