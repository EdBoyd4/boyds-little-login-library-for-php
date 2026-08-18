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
            
            $domain = $_SERVER['HTTP_HOST'] ?? '';
            // Chrome rejects domain=localhost, so we omit it for local development
            if ($domain === 'localhost' || $domain === '127.0.0.1' || str_contains($domain, 'localhost:')) {
                $domain = '';
            }
            
            $cookieParams = [
                'lifetime' => 0,
                'path' => '/',
                'secure' => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on',
                'httponly' => true,
                'samesite' => 'Lax'
            ];
            
            if ($domain !== '') {
                $cookieParams['domain'] = $domain;
            }
            
            // Set secure cookie params before starting session
            session_set_cookie_params($cookieParams);

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
            // Re-enabled 2026-08-16: the cookie-dropping this was worked around for was
            // traced to this local dev box's /etc/hosts mapping clariuminvestigations.local
            // to BOTH 127.0.0.1 and ::1 -- an idle connection could reconnect over the other
            // IP family and lose the session. Confirmed live: identical login -> 3-minute
            // wait -> submit sequence failed with the dual-stack mapping in place, succeeded
            // once the ::1 entry was removed. Already enabled and working on production
            // (which doesn't have this local-only dual-stack quirk); this brings dev back
            // in line with it.
            session_regenerate_id(true);
        }
    }

    public function enforceTimeout(): void
    {
        $this->startSession();
        
        $timeout = $this->config->getSessionTimeoutSeconds();
        if (isset($_SESSION['login_timestamp']) && (time() - $_SESSION['login_timestamp'] > $timeout)) {
            $this->destroySession();
            header("Location: " . $this->config->getLoginRoute() . "?timeout=1");
            exit;
        }
        $_SESSION['login_timestamp'] = time();
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
        unset($_SESSION['csrf_token']); // One-time use token
        return $valid;
    }
}
