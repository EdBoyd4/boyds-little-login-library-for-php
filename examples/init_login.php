<?php
declare(strict_types=1);

// 1. Tell your host application where the Login Library's Autoloader is located!
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/ExampleEnv.php';

// 2. Load the environment variables from your host application's root
// directory. Adjust this path to point to the actual directory containing
// .env. Login cannot function without these (no DB credentials, no
// connection), so a missing/unreadable .env fails loudly and immediately
// here -- not silently, to surface later as a confusing, unrelated-looking
// DB-connection error with an empty host/database name.
try {
    $env = ExampleEnv::load([__DIR__ . '/..']);
} catch (\RuntimeException $e) {
    error_log('init_login: could not load .env - ' . $e->getMessage());
    http_response_code(500);
    die('Configuration error. Please contact the site administrator.');
}

use Boyd\LoginLibrary\Config\LoginConfig;
use Boyd\LoginLibrary\Database\Database;
use Boyd\LoginLibrary\Repositories\UserRepository;
use Boyd\LoginLibrary\Repositories\AttemptRepository;
use Boyd\LoginLibrary\Repositories\SecurityAuditRepository;
use Boyd\LoginLibrary\Repositories\SettingsRepository;
use Boyd\LoginLibrary\Security\SessionManager;
use Boyd\LoginLibrary\Security\AuthManager;

// 3. Initialize the Library Configuration using the .env variables
$config = new LoginConfig(
    dbHost: $env['AUTH_DB_HOST'] ?? 'localhost',
    dbName: $env['AUTH_DB_NAME'] ?? '',
    dbUser: $env['AUTH_DB_USER'] ?? '',
    dbPass: $env['AUTH_DB_PASS'] ?? '',
    // Only examples/otp_login.php needs this: a random secret of 32+ characters,
    // e.g. `php -r 'echo bin2hex(random_bytes(32));'`. OtpManager refuses to
    // start without it.
    otpHmacKey: $env['AUTH_OTP_HMAC_KEY'] ?? ''
);

// 4. Wire up the library dependencies. SettingsRepository degrades
// gracefully if its table doesn't exist yet (see its own doc comment), so
// there's no cost to always making $sessionManager settings-aware here --
// every page that includes this file gets the runtime-adjustable session
// length for free, not just session_settings.php.
$database = new Database($config);
$userRepository = new UserRepository($database, $config);
$attemptRepository = new AttemptRepository($database);
$auditRepository = new SecurityAuditRepository($database);
$settingsRepository = new SettingsRepository($database, $config->getTableAuthSettings());
$sessionManager = new SessionManager($config, $settingsRepository);

// 5. Expose the $authManager to whatever file includes this script
$authManager = new AuthManager($userRepository, $sessionManager, $config, $attemptRepository, $auditRepository);
