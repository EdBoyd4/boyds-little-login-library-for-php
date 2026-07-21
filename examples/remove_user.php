<?php
require_once __DIR__ . '/init_login.php';

use Boyd\LoginLibrary\Controllers\UserController;
use Boyd\LoginLibrary\Views\RemoveUserView;

// Enforce login and System_Manager role
if ($authResult = $authManager->requireRole('System_Manager')) {
    header("Location: " . $authResult->redirectUrl);
    exit;
}

// Initialize Controller and View
$userController = new UserController($userRepository, $sessionManager, $auditRepository);
$removeUserView = new RemoveUserView();

// Handle the request
$result = $userController->handleRemoveUserRequest(
    method: $_SERVER['REQUEST_METHOD'],
    csrfToken: $_POST['csrf_token'] ?? null,
    userIdToRemove: isset($_POST['user_id']) ? (int)$_POST['user_id'] : null,
    ipAddress: $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0'
);

// Render the view
$removeUserView->render(
    pageTitle: 'Remove User',
    csrfToken: $result['csrfToken'],
    users: $result['users'],
    error: $result['error'],
    success: $result['success']
);
