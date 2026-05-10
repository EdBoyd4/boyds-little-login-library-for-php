<?php
declare(strict_types=1);

namespace Boyd\LoginLibrary\Controllers;

use Boyd\LoginLibrary\Security\AuthManager;
use Boyd\LoginLibrary\Security\SessionManager;
use Boyd\LoginLibrary\Views\LoginView;
use Exception;

class LoginController
{
    public function __construct(
        private AuthManager $authManager,
        private SessionManager $sessionManager,
        private LoginView $view
    ) {}

    public function handleRequest(string $pageTitle, string $detailedTitle, string $successRedirectUrl): void
    {
        if ($this->authManager->isLoggedIn()) {
            header("Location: " . $successRedirectUrl);
            exit;
        }

        $error = null;

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $csrfToken = $_POST['csrf_token'] ?? '';
            
            if (!$this->sessionManager->validateCsrfToken($csrfToken)) {
                $error = 'Invalid or expired form submission. Please try again.';
            } else {
                $username = trim((string)($_POST['username'] ?? ''));
                $password = trim((string)($_POST['password'] ?? ''));

                if (empty($username) || empty($password)) {
                    $error = 'Please enter both username and password.';
                } else {
                    try {
                        $this->authManager->login($username, $password);
                        header("Location: " . $successRedirectUrl);
                        exit;
                    } catch (Exception $e) {
                        // Note: To prevent user enumeration, we typically return a generic error message
                        $error = 'Invalid username or password.';
                    }
                }
            }
        }

        // Generate a new CSRF token for the form
        $newToken = $this->sessionManager->generateCsrfToken();
        $this->view->render($pageTitle, $detailedTitle, $newToken, $error);
    }
}
