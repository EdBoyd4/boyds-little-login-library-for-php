<?php
declare(strict_types=1);

// init_login.php loads .env, builds LoginConfig, and wires $database,
// $userRepository, $attemptRepository, $auditRepository, $sessionManager,
// and $authManager -- see that file for what each is. Every example that
// just needs a signed-in AuthManager includes it rather than repeating that
// setup itself.
require_once __DIR__ . '/init_login.php';

use Boyd\LoginLibrary\Views\LoginView;
use Boyd\LoginLibrary\Controllers\LoginController;

$view = new LoginView();
$controller = new LoginController($authManager, $sessionManager);

// Handle the login request
$result = $controller->handleLogin(
    method: $_SERVER['REQUEST_METHOD'],
    username: $_POST['username'] ?? null,
    password: $_POST['password'] ?? null,
    csrfToken: $_POST['csrf_token'] ?? null,
    requestedRedirect: $_GET['redirect'] ?? null,
    defaultRedirectUrl: '/example_secure_page.php', // Where users go after login
    ipAddress: $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0'
);

if ($result->success) {
    header("Location: " . $result->redirectUrl);
    exit;
}

// Render the view!
$view->render(
    pageTitle: 'Your Organization, Inc.',
    detailedTitle: 'Authorized Personnel Only',
    csrfToken: $result->csrfToken,
    error: $result->error
);
