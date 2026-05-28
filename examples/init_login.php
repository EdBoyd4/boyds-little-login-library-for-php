<?php
declare(strict_types=1);

// 1. Tell your host application where the Login Library's Autoloader is located!
require_once __DIR__ . '/../vendor/autoload.php';

// 2. Load the environment variables from your Host Application's root directory
// Adjust this path to point to the actual directory containing .env
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->safeLoad();

use Boyd\LoginLibrary\Config\LoginConfig;
use Boyd\LoginLibrary\Database\Database;
use Boyd\LoginLibrary\Repositories\UserRepository;
use Boyd\LoginLibrary\Repositories\AttemptRepository;
use Boyd\LoginLibrary\Repositories\SecurityAuditRepository;
use Boyd\LoginLibrary\Security\SessionManager;
use Boyd\LoginLibrary\Security\AuthManager;

// 3. Initialize the Library Configuration using the .env variables
$config = new LoginConfig(
    dbHost: $_ENV['AUTH_DB_HOST'] ?? 'localhost',
    dbName: $_ENV['AUTH_DB_NAME'] ?? '',
    dbUser: $_ENV['AUTH_DB_USER'] ?? '',
    dbPass: $_ENV['AUTH_DB_PASS'] ?? ''
);

// 4. Wire up the library dependencies
$database = new Database($config);
$userRepository = new UserRepository($database, $config);
$attemptRepository = new AttemptRepository($database);
$auditRepository = new SecurityAuditRepository($database);
$sessionManager = new SessionManager($config);

// 5. Expose the $authManager to whatever file includes this script
$authManager = new AuthManager($userRepository, $sessionManager, $config, $attemptRepository, $auditRepository);
