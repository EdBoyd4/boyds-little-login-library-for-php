<?php
require_once __DIR__ . '/init_login.php';

use Boyd\LoginLibrary\Controllers\UserController;
use Boyd\LoginLibrary\Views\AddUserView;

// Enforce login and CQM role
$authManager->requireRole('CQM');

// Initialize Controller and View
$userController = new UserController($userRepository, $sessionManager, $auditRepository);
$addUserView = new AddUserView();

// Handle the request
$result = $userController->handleAddUserRequest();

// Render the view
$addUserView->render(
    pageTitle: 'Add New User',
    csrfToken: $result['csrfToken'],
    roles: $result['roles'],
    error: $result['error'],
    success: $result['success']
);
