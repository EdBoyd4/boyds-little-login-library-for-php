<?php
declare(strict_types=1);

namespace Boyd\LoginLibrary\Security;

use Boyd\LoginLibrary\Models\User;
use Boyd\LoginLibrary\Repositories\UserRepository;
use Boyd\LoginLibrary\Repositories\AttemptRepository;
use Boyd\LoginLibrary\Config\LoginConfig;
use Exception;

class AuthManager
{
    public function __construct(
        private UserRepository $userRepository,
        private SessionManager $sessionManager,
        private LoginConfig $config,
        private ?AttemptRepository $attemptRepository = null
    ) {
        $this->enforceHttps();
    }

    public function enforceHttps(): void
    {
        if (empty($_SERVER['HTTPS']) || $_SERVER['HTTPS'] === "off") {
            $redirect = 'https://' . ($_SERVER['HTTP_HOST'] ?? '') . ($_SERVER['REQUEST_URI'] ?? '');
            header('Location: ' . $redirect);
            exit();
        }
    }

    /**
     * @throws Exception If login fails
     */
    public function login(string $username, string $password): void
    {
        $this->sessionManager->startSession();

        $ipAddress = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';

        if ($this->attemptRepository !== null) {
            $recentAttempts = $this->attemptRepository->countRecentAttempts($ipAddress, $this->config->getLockoutTimeSeconds());
            if ($recentAttempts >= $this->config->getMaxLoginAttempts()) {
                throw new Exception("Too many failed login attempts. Please try again later.");
            }
        }

        $user = $this->userRepository->findByUsername($username);

        if (!$user || !password_verify($password, $user->getPasswordHash())) {
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
    }

    public function logout(): void
    {
        $this->sessionManager->destroySession();
    }

    public function isLoggedIn(): bool
    {
        $this->sessionManager->startSession();
        return isset($_SESSION['user_id']);
    }

    public function enforceLogin(): void
    {
        if (!$this->isLoggedIn()) {
            header("Location: " . $this->config->getLoginRoute());
            exit;
        }
        $this->sessionManager->enforceTimeout();
    }

    public function requireRole(string $requiredRole): void
    {
        $this->enforceLogin();
        $userRoles = $_SESSION['user_roles'] ?? [];
        if (!in_array($requiredRole, $userRoles, true)) {
            header("Location: " . $this->config->getUnauthorizedRoute());
            exit;
        }
    }

    public function requireAllRoles(array $requiredRoles): void
    {
        $this->enforceLogin();
        $userRoles = $_SESSION['user_roles'] ?? [];
        
        foreach ($requiredRoles as $role) {
            if (!in_array($role, $userRoles, true)) {
                header("Location: " . $this->config->getUnauthorizedRoute());
                exit;
            }
        }
    }
}
