<?php
session_start();

require_once CLAR_LOGIN_LIBRARY_CONSTANTS . '/boyds-little-login-library-for-php_user_constants.php';

class Auth
{
    public static function enforceHttps(): void
    {
        if (empty($_SERVER['HTTPS']) || $_SERVER['HTTPS'] === "off") {
            $redirect = 'https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
            header('Location: ' . $redirect);
            exit();
        }
    }

    public static function enforceLogin(): void
    {
        if (empty($_SESSION[UN_]) || empty($_SESSION[URSS])) {
            header("Location: /login.php");
            exit;
        }
    }

    // Require specific role
    public static function requireRole(string $requiredRole): void
    {
        if (empty($_SESSION[URSS]) || !in_array($requiredRole, $_SESSION[URSS])) {
            header("Location: /unauthorized.php");
            exit;
        }
    }

    // Require multiple roles
    public static function requireAllRoles(array $requiredRoles): void
    {
        if (empty($_SESSION[URSS]) || !is_array($_SESSION[URSS])) {
            header("Location: /unauthorized.php");
            exit;
        }

        foreach ($requiredRoles as $role) {
            if (!in_array($role, $_SESSION[URSS], true)) {
                header("Location: /unauthorized.php");
                exit;
            }
        }
    }

    // OPTIONAL: Allow multiple roles
    /* public static function requireAnyRole(array $allowedRoles): void
    {
        if (!isset($_SESSION[UR_]) || !in_array($_SESSION[UR_], $allowedRoles, true)) {
            header("Location: /unauthorized.php");
            exit;
        }
    } */

    // OPTIONAL: Session expiration enforcement
    public static function enforceSessionTimeout(int $timeoutSeconds = 1800): void // 30 minutes
    {
        if (!isset($_SESSION['login_timestamp'])) {
            header("Location: /login.php");
            exit;
        }
        if (time() - $_SESSION['login_timestamp'] > $timeoutSeconds) {
            session_unset();
            session_destroy();
            header("Location: /login.php?timeout=1");
            exit;
        }
    }

    public static function generateCsrfToken(): string
    {
        $token = bin2hex(random_bytes(32));
        $_SESSION[CSRF] = $token;              
        return $token;
    }

    public static function validateCsrfToken(): void
    {
        if (
            empty($_POST[CSRF]) ||
            empty($_SESSION[CSRF]) ||
            !hash_equals($_SESSION[CSRF], $_POST[CSRF])
        ) {
            die('Invalid CSRF token.');
        }
    }
}
