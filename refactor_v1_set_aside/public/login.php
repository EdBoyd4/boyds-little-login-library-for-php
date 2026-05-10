<?php
declare(strict_types=1);

// When using this in a real project, point this to the composer autoload file.
// e.g. require_once __DIR__ . '/../vendor/autoload.php';
require_once dirname(__DIR__) . '/vendor/autoload.php';

use Boyd\LoginLibrary\Config\LoginConfig;
use Boyd\LoginLibrary\Database\Database;
use Boyd\LoginLibrary\Repositories\UserRepository;
use Boyd\LoginLibrary\Security\SessionManager;
use Boyd\LoginLibrary\Security\AuthManager;
use Boyd\LoginLibrary\Views\LoginView;
use Boyd\LoginLibrary\Controllers\LoginController;

// 1. Initialize Configuration (typically populated from a .env file or project constants)
$config = new LoginConfig(
    dbHost: 'localhost',
    dbName: 'clarassign_db', // Adjust this per project
    dbUser: 'root',          // Adjust this per project
    dbPass: 'secret',        // Adjust this per project
    sessionName: 'BoydLibraryAuthSession',
    sessionTimeoutSeconds: 1800,
    loginRoute: '/login.php',
    unauthorizedRoute: '/unauthorized.php'
);

// 2. Wire up the dependencies
$database = new Database($config);
$userRepository = new UserRepository($database, $config);
$sessionManager = new SessionManager($config);
$authManager = new AuthManager($userRepository, $sessionManager, $config);
$view = new LoginView();
$controller = new LoginController($authManager, $sessionManager, $view);

// 3. Handle the incoming request
$controller->handleRequest(
    pageTitle: 'Project Log In',
    detailedTitle: 'Please log in below',
    successRedirectUrl: '/dashboard.php' // Where to go upon successful login
);
