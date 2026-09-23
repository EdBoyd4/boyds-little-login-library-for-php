# Boyd's Little Login Library for PHP

A reusable, secure, Object-Oriented PHP login library designed with data security best practices in mind. It provides a robust architecture for managing user authentication, role-based access control, and mitigating common security threats. It is oriented to projects that have lower levels of traffic but still need secure login functionality. It was originally coded by a security specialist, but has benefited from Google Antigravity (Gemma 3) for code improvements and refactoring. It has not been battle-tested in high-traffic environments, so if you are developing an application with high-traffic expectations, thorough security testing is recommended before deploying in production.

## 🔒 Security Features

- **Brute-Force Protection**: Automatically tracks failed login attempts and locks out IP addresses that exceed a configurable threshold.
- **Security Audit Logging**: Persistently records security events (e.g., successful logins, logouts, lockouts, session discrepancies) into a dedicated database table for auditing purposes.
- **Secure Password Hashing**: Utilizes PHP's native, strong password hashing algorithms.
- **Environment-Based Configuration**: Uses `vlucas/phpdotenv` to keep database credentials and configuration settings securely out of the source code.
- **Session Management**: Configurable session timeouts and strict session handling to prevent session hijacking.
- **Role-Based Authorization**: Built-in support for user roles and mapping, allowing fine-grained access control on routes and actions.
- **PSR-4 Compliant**: Fully namespaced and autoloadable via Composer.

## ⚠️ Known Issues / Development Notes

- **Session Regeneration**: In `SessionManager.php`, the `session_regenerate_id(true)` function call has been temporarily commented out for local development. This is because some modern browsers (e.g., Chrome) enforce strict `Secure/SameSite` behaviors during POST redirects on localhost IPv6 (`::1`) environments, which causes cookies to be dropped and the session to be lost. **CRITICAL: You MUST re-enable this function call before deploying to a production environment to protect against session fixation attacks.**

## 📋 Requirements

- PHP >= 8.1 (uses native enums)
- Composer
- A relational database (e.g., MySQL, MariaDB) with PDO extension enabled.

## 🚀 Installation

1. **Install via Composer:**
   Ensure you have Composer installed. While not published to Packagist yet, you can require it by defining the repository in your `composer.json` or copying it directly into your project if used as a local module.
   ```bash
   composer require ed-boyd/boyds-little-login-library-for-php
   ```

2. **Set up Environment Variables:**
   Copy your `.env.example` to `.env` in your project root and configure your database and application settings. (A template will be provided by your host application).

3. **Create the Database Tables:**
   Your application owns its schema. `database/create_example_database.php` is the reference for the tables the library expects (and, for a trial run, creates them in an existing empty database, plus the default `system_manager` role). Connection settings come from environment variables so the password never appears in a process list:
   ```bash
   AUTH_DB_NAME=login_example AUTH_DB_USER=username AUTH_DB_PASS=secret \
     php vendor/ed-boyd/boyds-little-login-library-for-php/database/create_example_database.php
   ```

   *Note: Because creating users requires the `system_manager` role, you must create your very first user directly in the database (or via an initial setup script) and map them to the `system_manager` role ID.*

## ⚙️ Configuration

The library uses a `LoginConfig` object that parses settings from your `.env` file. Common configuration options include:
- Database Connection (Host, User, Password, DB Name)
- Table/Column Overrides (if your existing schema differs from the defaults)
- Throttling Settings (`maxLoginAttempts`, `lockoutTimeMinutes`)
- Session Timers (`sessionTimeoutSeconds`)

## 💡 Usage Example

### Initializing the Authentication Manager

```php
<?php
require 'vendor/autoload.php';

use Boyd\LoginLibrary\Config\LoginConfig;
use Boyd\LoginLibrary\Database\Database;
use Boyd\LoginLibrary\Repositories\AttemptRepository;
use Boyd\LoginLibrary\Repositories\UserRepository;
use Boyd\LoginLibrary\Repositories\SecurityAuditRepository;
use Boyd\LoginLibrary\Security\AuthManager;

// 1. Load your .env (handled by host app usually)
// $dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
// $dotenv->load();

// 2. Build configuration
$config = new LoginConfig(
    $_ENV['AUTH_DB_HOST'],
    $_ENV['AUTH_DB_NAME'],
    $_ENV['AUTH_DB_USER'],
    $_ENV['AUTH_DB_PASS']
);

// 3. Connect to Database
$db = new Database($config);

// 4. Instantiate Repositories
$userRepo = new UserRepository($db, $config);
$attemptRepo = new AttemptRepository($db);
$auditRepo = new SecurityAuditRepository($db);

// 5. Create AuthManager
$authManager = new AuthManager($userRepo, $attemptRepo, $auditRepo, $config);
```

### Logging a User In

```php
// Assuming $authManager is available from a Dependency Injection container
$username = $_POST['username'] ?? '';
$password = $_POST['password'] ?? '';
$ipAddress = $_SERVER['REMOTE_ADDR'];

if ($authManager->login($username, $password, $ipAddress)) {
    // Login successful
    header("Location: /dashboard.php");
    exit;
} else {
    // Login failed
    $errorMessage = "Invalid credentials or account locked.";
}
```

### Protecting a Route

```php
// On a restricted page (e.g., dashboard.php)
$authManager->enforceLogin();

// Optionally, require a specific role
$authManager->requireRole('system_manager');

// If the script reaches this point, the user is authenticated and authorized!
```

### Passwordless Sign-In (Email OTP)

An opt-in, self-contained flow: the user enters an email, receives a short-lived
numeric code, and enters it to sign in. No password is involved.

The library issues and verifies codes but **does not send email** — you supply a
mailer by implementing `Boyd\LoginLibrary\Contracts\OtpMailerInterface`. Whatever
that implementation actually sends mail with (PHPMailer, Symfony Mailer, a raw
`mail()` call, a transactional email API, ...) is your choice, and its
dependency belongs in **your own** `composer.json` — this library has none.

```php
use Boyd\LoginLibrary\Contracts\OtpMailerInterface;
use Boyd\LoginLibrary\Repositories\OtpRepository;
use Boyd\LoginLibrary\Security\OtpManager;
use Boyd\LoginLibrary\Security\OtpVerificationOutcome;
use Boyd\LoginLibrary\Controllers\SelfServiceOtpController;

final class MyMailer implements OtpMailerInterface
{
    public function sendOtp(string $toEmail, string $code, int $ttlSeconds): void
    {
        // hand $code to your mail transport; throw on hard failure
    }
}

// $config must carry otpHmacKey: a random secret of 32+ characters kept outside
// the database (e.g. in .env). Generate one with:
//   php -r 'echo bin2hex(random_bytes(32));'
$otpManager = OtpManager::fromConfig(
    new OtpRepository($db, $config->getTableOtpChallenges()),
    new MyMailer(),
    $config,
    $auditRepo            // optional Boyd\LoginLibrary\Contracts\SecurityAuditLoggerInterface
);

// Step 1 — email a code (neutral response whether or not the address exists)
$otpManager->startChallenge($email, $ip);

// Step 2 — verify
$outcome = $otpManager->verifyChallenge($email, $code, $ip);
if ($outcome === OtpVerificationOutcome::Verified) {
    $user = $userRepo->findByUsername($email);   // store the email in user_name
    $authManager->establishPasswordlessSession($user, $ip);
}
```

`SelfServiceOtpController` wires both steps to a page (CSRF, redirects, neutral
messaging, first-time registration); see `examples/otp_login.php`.

**Not a second factor.** A verified code alone establishes a session — there is
no password check anywhere in this flow. Do not use it as an *alternate* login
for an account that also has a password: letting either credential suffice on
its own weakens the account to whichever is weaker, not stronger (see the
`2FA/MFA` item in `docs/boyds-little-login-library-for-php-TODO.md`). This
library previously shipped an `OtpController` built for exactly that pattern
against a pre-registered, password-bearing account; it was removed
2026-09-23 for this reason.

**Built-in abuse controls:** one live code per address (a new code supersedes the
old), a per-code attempt ceiling, a resend cooldown, and a per-address hourly
send cap. Codes are stored only as an HMAC-SHA256 keyed with `otpHmacKey` and
bound to the address, so a leak of the database alone cannot reveal or forge a
live code (a plain password hash would not help: a 6-digit code falls to offline
brute force in minutes). Rotating the key only invalidates codes issued in the
last few minutes. Tune via `LoginConfig` (`otpCodeLength`, `otpTtlSeconds`,
`otpMaxAttempts`, `otpResendCooldownSeconds`, `otpMaxSendsPerHour`).

**Session length:** a password session idles out after `sessionTimeoutSeconds`
(4 hours by default); a session established by a verified code uses the shorter
`otpSessionTimeoutSeconds` (30 minutes by default).

**Self-service, roles-free store:** `PasswordlessUserRepository` +
`SelfServiceOtpController` register a first-time verified address in a bare
`users` table (email + timestamps -- no password, no roles tables); every user
carries the single fixed role the repository was constructed with.

Schema: the `otp_challenges` and `auth_settings` tables are defined in
`database/create_example_database.php`.

### Runtime-Adjustable Session Length

By default the idle timeout is the static `LoginConfig::$sessionTimeoutSeconds`.
Pass a `SettingsRepository` into `SessionManager` and an administrator can change
it at runtime (stored in `auth_settings`, clamped to the configured min/max):

```php
$settings = new SettingsRepository($db, $config->getTableAuthSettings());
$sessionManager = new SessionManager($config, $settings);   // now honours the stored value
```

`SettingsController` + `SessionSettingsView` provide a ready admin page — see
`examples/session_settings.php`. With no `SettingsRepository` the behaviour is
exactly as before.

## 🧪 Tests

```bash
composer install
composer test        # or: vendor/bin/phpunit
```

The suite covers the OTP module end to end (issue, verify, single-use, lockout,
rate-limit, expiry, supersede, HMAC binding). CI runs it on PHP 8.1–8.3.

## 📚 Documentation & Examples

- Check the `docs/` directory for Architectural Decision Records (ADRs) and ongoing TODOs.
- Check the `examples/` directory for complete working examples of initialization, login forms, and secure pages.

## 📄 License

This project is licensed under the MIT License - see the [LICENSE.md](LICENSE.md) file for details.
