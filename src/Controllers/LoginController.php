<?php
declare(strict_types=1);

namespace Boyd\LoginLibrary\Controllers;

use Boyd\LoginLibrary\Security\AuthManager;
use Boyd\LoginLibrary\Security\SessionManager;
use Exception;

class LoginController
{
    public function __construct(
        private AuthManager $authManager,
        private SessionManager $sessionManager
    ) {}

    /**
     * Handles the login page request.
     * Returns an AuthResult containing success status, any error messages, redirect URL, and CSRF token.
     */
    public function handleLogin(
        string $method,
        ?string $username,
        ?string $password,
        ?string $csrfToken,
        ?string $requestedRedirect,
        string $defaultRedirectUrl,
        string $ipAddress = '0.0.0.0'
    ): \Boyd\LoginLibrary\Models\AuthResult {
        // 1. Determine the target redirect URL dynamically
        $redirectUrl = $defaultRedirectUrl;
        
        // Allow dynamic redirecting if requested via URL
        if (!empty($requestedRedirect) && str_starts_with($requestedRedirect, '/')) {
            $redirectUrl = $requestedRedirect;
        }

        // 1. If the user is already logged in, destroy their session to ensure a clean slate.
        if ($this->authManager->isLoggedIn()) {
            $this->authManager->logout($ipAddress);
        }

        $error = null;

        // 2. Process the login attempt if this is a POST request
        if ($method === 'POST') {
            // Validate CSRF
            if (!$this->sessionManager->validateCsrfToken($csrfToken ?? '')) {
                $error = 'Your session expired or form submission was invalid. Please try again.';
            } else {
                $user = trim($username ?? '');
                $pass = trim($password ?? '');

                if (empty($user) || empty($pass)) {
                    $error = 'Please complete all the required fields.';
                } else {
                    try {
                        $this->authManager->login($user, $pass, $ipAddress);
                        
                        // Success! Return the AuthResult with the redirectUrl
                        return new \Boyd\LoginLibrary\Models\AuthResult(true, null, $redirectUrl, $this->sessionManager->generateCsrfToken());
                    } catch (Exception $e) {
                        $error = $e->getMessage();
                    }
                }
            }
        }

        // 3. Return the current state for rendering
        return new \Boyd\LoginLibrary\Models\AuthResult(false, $error, null, $this->sessionManager->generateCsrfToken());
    }
}
