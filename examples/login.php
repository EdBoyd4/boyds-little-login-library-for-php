<?php
declare(strict_types=1);

// 1. Tell your host application where the Login Library's Autoloader is located!
require_once __DIR__ . '/../vendor/autoload.php';

// 2. Load the environment variables from your Host Application's root directory
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->safeLoad();

use Boyd\LoginLibrary\Config\LoginConfig;
use Boyd\LoginLibrary\Database\Database;
use Boyd\LoginLibrary\Repositories\UserRepository;
use Boyd\LoginLibrary\Repositories\AttemptRepository;
use Boyd\LoginLibrary\Security\SessionManager;
use Boyd\LoginLibrary\Security\AuthManager;
use Boyd\LoginLibrary\Views\LoginView;
use Boyd\LoginLibrary\Controllers\LoginController;

// 3. Initialize the Library Configuration using the .env variables
$config = new LoginConfig(
    dbHost: $_ENV['AUTH_DB_HOST'] ?? 'localhost',
    dbName: $_ENV['AUTH_DB_NAME'] ?? '',
    dbUser: $_ENV['AUTH_DB_USER'] ?? '',
    dbPass: $_ENV['AUTH_DB_PASS'] ?? ''
);

// 3. Wire up the library dependencies
$database = new Database($config);
$userRepository = new UserRepository($database, $config);
$attemptRepository = new AttemptRepository($database);
$sessionManager = new SessionManager($config);
$authManager = new AuthManager($userRepository, $sessionManager, $config, $attemptRepository);
$view = new LoginView();
$controller = new LoginController($authManager, $sessionManager);

// 4. Handle the login request
$result = $controller->handleRequest('/example_secure_page.php'); // Where users go after login

// 5. Render the View!
$view->render(
    pageTitle: 'Your Organization, Inc.',
    detailedTitle: 'Authorized Personnel Only',
    csrfToken: $result['csrfToken'],
    error: $result['error']
);
