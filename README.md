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

## 📋 Requirements

- PHP >= 8.0
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

3. **Initialize the Database Schema:**
   Import the provided SQL schema into your database. This will create the required tables and insert the default roles (including `system_manager`).
   ```bash
   mysql -u username -p database_name < vendor/ed-boyd/boyds-little-login-library-for-php/database/schema.sql
   ```
   
   *Note: Because creating users requires the `System_Manager` role, you must create your very first user directly in the database (or via an initial setup script) and map them to the `system_manager` role ID.*

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
    $_ENV['DB_HOST'],
    $_ENV['DB_NAME'],
    $_ENV['DB_USER'],
    $_ENV['DB_PASS']
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
$authManager->requireRole('System_Manager');

// If the script reaches this point, the user is authenticated and authorized!
```

## 📚 Documentation & Examples

- Check the `docs/` directory for Architectural Decision Records (ADRs) and ongoing TODOs.
- Check the `examples/` directory for complete working examples of initialization, login forms, and secure pages.

## 📄 License

This project is licensed under the MIT License - see the [LICENSE.md](LICENSE.md) file for details.
