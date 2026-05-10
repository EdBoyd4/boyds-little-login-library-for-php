<?php
declare(strict_types=1);

namespace Boyd\LoginLibrary\Security;

use Boyd\LoginLibrary\Models\User;
use Boyd\LoginLibrary\Repositories\UserRepository;
use Boyd\LoginLibrary\Config\LoginConfig;
use Exception;

class AuthManager
{
    public function __construct(
        private UserRepository $userRepository,
        private SessionManager $sessionManager,
        private LoginConfig $config
    ) {}

    /**
     * @throws Exception If login fails
     */
    public function login(string $username, string $password): void
    {
        $this->sessionManager->startSession();

        $user = $this->userRepository->findByUsername($username);

        if (!$user) {
            throw new Exception("Invalid username or password.");
        }

        if (!password_verify($password, $user->getPasswordHash())) {
            throw new Exception("Invalid username or password.");
        }

        // Prevent session fixation
        $this->sessionManager->regenerate();

        $_SESSION['user_id'] = $user->getId();
        $_SESSION['user_name'] = $user->getUsername();
        $_SESSION['user_role'] = $user->getRole();
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

    public function requireRole(string|int $requiredRole): void
    {
        $this->enforceLogin();
        if ($_SESSION['user_role'] !== $requiredRole) {
            header("Location: " . $this->config->getUnauthorizedRoute());
            exit;
        }
    }
}
