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
     * Handles the full lifecycle of a login page request (both GET and POST).
     * Returns an array containing any error messages and the current CSRF token,
     * which the View will use to render the HTML.
     *
     * @param string $successRedirectUrl The URL to redirect to upon successful login.
     * @return array{error: ?string, csrfToken: string}
     */
    public function handleRequest(string $defaultRedirectUrl): array
    {
        // 1. Determine the target redirect URL dynamically
        $redirectUrl = $defaultRedirectUrl;
        
        // Allow dynamic redirecting if requested via URL (e.g., login.php?redirect=/reports.php)
        if (!empty($_GET['redirect']) && is_string($_GET['redirect'])) {
            $requestedRedirect = $_GET['redirect'];
            // Security: Only allow relative paths starting with a forward slash to prevent Open Redirect attacks!
            if (str_starts_with($requestedRedirect, '/')) {
                $redirectUrl = $requestedRedirect;
            }
        }
        // 1. If the user is already logged in and visits the login page, 
        // we destroy their session to ensure a clean slate and force them to re-authenticate.
        if ($this->authManager->isLoggedIn()) {
            $this->authManager->logout();
        }

        $error = null;

        // 2. Process the login attempt if this is a POST request
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $csrfToken = $_POST['csrf_token'] ?? '';
            
            // Validate CSRF
            if (!$this->sessionManager->validateCsrfToken($csrfToken)) {
                http_response_code(403);
                $error = 'Your session expired or form submission was invalid. Please try again.';
            } else {
                $username = trim((string)($_POST['username'] ?? ''));
                $password = trim((string)($_POST['password'] ?? '')); // Using literal string for password, not constant

                // Verify they actually typed something
                if (empty($username) || empty($password)) {
                    $error = 'Please complete all the required fields.';
                } else {
                    // Attempt the actual login
                    try {
                        $this->authManager->login($username, $password);
                        
                        // Success! Redirect to the destination.
                        header("Location: " . $redirectUrl);
                        exit;
                    } catch (Exception $e) {
                        // AuthManager throws Exceptions if the user doesn't exist or password fails.
                        // We catch it and display a generic error.
                        $error = $e->getMessage();
                    }
                }
            }
        }

        // 3. Generate a fresh CSRF token for the page render
        return [
            'error' => $error,
            'csrfToken' => $this->sessionManager->generateCsrfToken()
        ];
    }
}
