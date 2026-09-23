<?php
declare(strict_types=1);

require_once __DIR__ . '/init_login.php';

use Boyd\LoginLibrary\Controllers\UserController;
use Boyd\LoginLibrary\Views\AddUserView;

// Enforce login and system_manager role
if ($authResult = $authManager->requireRole('system_manager')) {
    header("Location: " . $authResult->redirectUrl);
    exit;
}

// Initialize Controller and View
$userController = new UserController($userRepository, $sessionManager, $auditRepository);
$addUserView = new AddUserView();

// Handle the request
$result = $userController->handleAddUserRequest(
    method: $_SERVER['REQUEST_METHOD'],
    csrfToken: $_POST['csrf_token'] ?? null,
    username: $_POST['username'] ?? null,
    password: $_POST['password'] ?? null,
    roles: $_POST['roles'] ?? [],
    ipAddress: $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0'
);

// Render the view
$addUserView->render(
    pageTitle: 'Add New User',
    csrfToken: $result['csrfToken'],
    roles: $result['roles'],
    error: $result['error'],
    success: $result['success']
);
