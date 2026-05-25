<?php
require_once __DIR__ . '/init_login.php';

use Boyd\LoginLibrary\Controllers\PasswordController;
use Boyd\LoginLibrary\Views\ChangePasswordView;

// Enforce login
$authManager->enforceLogin();

// Initialize Controller and View
$passwordController = new PasswordController($userRepository, $sessionManager, $auditRepository);
$changePasswordView = new ChangePasswordView();

// Handle the request
$result = $passwordController->handleChangePasswordRequest();

// Render the view
$changePasswordView->render(
    pageTitle: 'Change Password',
    csrfToken: $result['csrfToken'],
    error: $result['error'],
    success: $result['success']
);
