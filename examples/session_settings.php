<?php
declare(strict_types=1);

// Admin page: set how long an idle session lasts before auto-logout. The value
// is stored in the auth_settings table and read by SessionManager on every
// request. Requires the user-manager role (default 'system_manager').
//
// init_login.php loads .env, builds LoginConfig, and wires $database,
// $userRepository, $attemptRepository, $auditRepository, $settingsRepository,
// $sessionManager (settings-aware), and $authManager -- see that file for
// what each is. Nothing to rebuild here.
require_once __DIR__ . '/init_login.php';

use Boyd\LoginLibrary\Controllers\SettingsController;
use Boyd\LoginLibrary\Views\SessionSettingsView;

// Must be signed in AND hold the user-manager role.
if ($redirect = $authManager->requireRole($config->getUserManagerRole())) {
    header('Location: ' . $redirect->redirectUrl);
    exit;
}

$controller = new SettingsController($settingsRepository, $sessionManager, $config, $auditRepository);
$state = $controller->handleSessionTimeoutRequest(
    method: $_SERVER['REQUEST_METHOD'],
    csrfToken: $_POST['csrf_token'] ?? null,
    submittedSeconds: $_POST['seconds'] ?? null,
    ipAddress: $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0'
);

(new SessionSettingsView())->render(
    pageTitle: 'Session Settings',
    csrfToken: $state['csrfToken'],
    currentSeconds: $state['currentSeconds'],
    min: $state['min'],
    max: $state['max'],
    error: $state['error'],
    success: $state['success']
);
