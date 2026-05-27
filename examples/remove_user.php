<?php
require_once __DIR__ . '/init_login.php';

use Boyd\LoginLibrary\Controllers\UserController;
use Boyd\LoginLibrary\Views\RemoveUserView;

// Enforce login and System_Manager role
$authManager->requireRole('System_Manager');

// Initialize Controller and View
$userController = new UserController($userRepository, $sessionManager, $auditRepository);
$removeUserView = new RemoveUserView();

// Handle the request
$result = $userController->handleRemoveUserRequest();

// Render the view
$removeUserView->render(
    pageTitle: 'Remove User',
    csrfToken: $result['csrfToken'],
    users: $result['users'],
    error: $result['error'],
    success: $result['success']
);
