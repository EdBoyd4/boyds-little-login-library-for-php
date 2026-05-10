<?php
declare(strict_types=1);

// 1. Tell Clarium where the Login Library's Autoloader is located!
require_once '/home/xnbglkce/boyds-little-login-library-for-php/vendor/autoload.php';

// 2. Load the environment variables from Clarium's root directory
$dotenv = Dotenv\Dotenv::createImmutable('/home/xnbglkce/clarium');
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
    dbHost: $_ENV['DB_HOST'] ?? 'localhost',
    dbName: $_ENV['DB_NAME'] ?? '',
    dbUser: $_ENV['DB_USER'] ?? '',
    dbPass: $_ENV['DB_PASS'] ?? ''
);

// 3. Wire up the library dependencies
$database = new Database($config);
$userRepository = new UserRepository($database, $config);
$attemptRepository = new AttemptRepository($database);
$sessionManager = new SessionManager($config);
$authManager = new AuthManager($userRepository, $sessionManager, $config, $attemptRepository);
$view = new LoginView();
$controller = new LoginController($authManager, $sessionManager);

// 4. Handle the Clarium login request
$result = $controller->handleRequest('/dashboard.php'); // Where Clarium users go after login

// 5. Render the View!
$view->render(
    pageTitle: 'Clarium Investigations, Inc.',
    detailedTitle: 'Professional Users Log In',
    csrfToken: $result['csrfToken'],
    error: $result['error']
);
