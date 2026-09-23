<?php
declare(strict_types=1);

require_once __DIR__ . '/init_login.php';

use Boyd\LoginLibrary\Controllers\PasswordController;
use Boyd\LoginLibrary\Views\ChangePasswordView;

// Enforce login
if ($authResult = $authManager->enforceLogin()) {
    header("Location: " . $authResult->redirectUrl);
    exit;
}

// Initialize Controller and View
$passwordController = new PasswordController($userRepository, $sessionManager, $auditRepository);
$changePasswordView = new ChangePasswordView();

// Handle the request
$result = $passwordController->handleChangePasswordRequest(
    method: $_SERVER['REQUEST_METHOD'],
    csrfToken: $_POST['csrf_token'] ?? null,
    currentPassword: $_POST['current_password'] ?? null,
    newPassword: $_POST['new_password'] ?? null,
    confirmPassword: $_POST['confirm_password'] ?? null,
    ipAddress: $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0'
);

// Render the view
$changePasswordView->render(
    pageTitle: 'Change Password',
    csrfToken: $result['csrfToken'],
    error: $result['error'],
    success: $result['success']
);
