<?php
declare(strict_types=1);

namespace Boyd\LoginLibrary\Security;

use Boyd\LoginLibrary\Config\LoginConfig;

class SessionManager
{
    public function __construct(private LoginConfig $config) {}

    public function startSession(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_name($this->config->getSessionName());
            
            // Set secure cookie params before starting session
            session_set_cookie_params([
                'lifetime' => 0,
                'path' => '/',
                'domain' => $_SERVER['HTTP_HOST'] ?? '',
                'secure' => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on',
                'httponly' => true,
                'samesite' => 'Strict'
            ]);

            session_start();
        }
    }

    public function destroySession(): void
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            return;
        }

        $_SESSION = [];
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]
            );
        }
        session_destroy();
    }

    public function regenerate(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_regenerate_id(true);
        }
    }

    public function enforceTimeout(): void
    {
        $this->startSession();
        
        $timeout = $this->config->getSessionTimeoutSeconds();
        if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > $timeout)) {
            $this->destroySession();
            header("Location: " . $this->config->getLoginRoute() . "?timeout=1");
            exit;
        }
        $_SESSION['last_activity'] = time();
    }

    public function generateCsrfToken(): string
    {
        $this->startSession();
        $token = bin2hex(random_bytes(32));
        $_SESSION['csrf_token'] = $token;
        return $token;
    }

    public function validateCsrfToken(?string $token): bool
    {
        $this->startSession();
        if (empty($token) || empty($_SESSION['csrf_token'])) {
            return false;
        }
        
        $valid = hash_equals($_SESSION['csrf_token'], $token);
        unset($_SESSION['csrf_token']); // One-time use
        return $valid;
    }
}
