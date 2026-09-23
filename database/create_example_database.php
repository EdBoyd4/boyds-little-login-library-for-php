<?php
declare(strict_types=1);

/**
 * Creates the tables the library's examples/ need, in an EXISTING, empty MySQL /
 * MariaDB database. For trying the library out only -- a real application owns
 * its own schema and migrations.
 *
 * Connection settings come from the process environment (the same names
 * examples/init_login.php reads from .env); the password never touches argv,
 * so it can't show up in a process list:
 *
 *   AUTH_DB_HOST=localhost AUTH_DB_NAME=login_example \
 *   AUTH_DB_USER=me AUTH_DB_PASS=secret \
 *   php database/create_example_database.php
 *
 * Safe to re-run: every statement is CREATE TABLE IF NOT EXISTS, and the one
 * seed row is INSERT IGNORE.
 *
 * Table names match LoginConfig's defaults. OTP challenges are email-only by
 * policy (OtpManager enforces this): `identifier` is the lower-cased address.
 */

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

$host = getenv('AUTH_DB_HOST') ?: 'localhost';
$name = getenv('AUTH_DB_NAME') ?: '';
$user = getenv('AUTH_DB_USER') ?: '';
$pass = getenv('AUTH_DB_PASS') ?: '';

if ($name === '' || $user === '') {
    fwrite(STDERR, "Set AUTH_DB_NAME and AUTH_DB_USER (and AUTH_DB_HOST / AUTH_DB_PASS if needed).\n");
    exit(1);
}

$tableOptions = 'ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci';

$statements = [
    "CREATE TABLE IF NOT EXISTS users (
        user_id    INT AUTO_INCREMENT PRIMARY KEY,
        user_name  VARCHAR(255) NOT NULL UNIQUE,
        password   VARCHAR(255) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) $tableOptions",

    "CREATE TABLE IF NOT EXISTS roles (
        role_id INT AUTO_INCREMENT PRIMARY KEY,
        role    VARCHAR(100) NOT NULL UNIQUE
    ) $tableOptions",

    "CREATE TABLE IF NOT EXISTS user_roles (
        user_id INT NOT NULL,
        role_id INT NOT NULL,
        PRIMARY KEY (user_id, role_id),
        FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
        FOREIGN KEY (role_id) REFERENCES roles(role_id) ON DELETE CASCADE
    ) $tableOptions",

    // Brute-force protection: shared by password login and OTP (IpAttemptThrottle).
    "CREATE TABLE IF NOT EXISTS login_attempts (
        id           INT AUTO_INCREMENT PRIMARY KEY,
        ip_address   VARCHAR(45) NOT NULL,
        username     VARCHAR(255) NOT NULL,
        attempt_time INT NOT NULL,
        INDEX idx_login_attempts_ip (ip_address)
    ) $tableOptions",

    "CREATE TABLE IF NOT EXISTS security_audit_logs (
        id                INT AUTO_INCREMENT PRIMARY KEY,
        event_type        VARCHAR(50) NOT NULL,
        ip_address        VARCHAR(45) NOT NULL,
        username_involved VARCHAR(255) NULL,
        details           TEXT NULL,
        created_at        TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_audit_ip (ip_address),
        INDEX idx_audit_user (username_involved),
        INDEX idx_audit_event (event_type)
    ) $tableOptions",

    // Runtime-tunable knobs (currently just session length). Optional: without
    // this table the library falls back to LoginConfig's static defaults.
    "CREATE TABLE IF NOT EXISTS auth_settings (
        setting_key   VARCHAR(64) NOT NULL PRIMARY KEY,
        setting_value VARCHAR(255) NOT NULL,
        updated_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) $tableOptions",

    // One row per issued code; only the password_hash() of the code is stored.
    "CREATE TABLE IF NOT EXISTS otp_challenges (
        id          INT AUTO_INCREMENT PRIMARY KEY,
        identifier  VARCHAR(255) NOT NULL,
        code_hash   VARCHAR(255) NOT NULL,
        expires_at  INT NOT NULL,
        attempts    INT NOT NULL DEFAULT 0,
        consumed_at INT NULL,
        created_at  INT NOT NULL,
        INDEX idx_otp_identifier (identifier)
    ) $tableOptions",

    // UserController checks for this role by default before allowing users to
    // be added or removed; pass your own role name to it to use another.
    "INSERT IGNORE INTO roles (role) VALUES ('system_manager')",
];

try {
    $pdo = new PDO(
        "mysql:host={$host};dbname={$name};charset=utf8mb4",
        $user,
        $pass,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    foreach ($statements as $sql) {
        $pdo->exec($sql);
    }
} catch (PDOException $e) {
    fwrite(STDERR, 'Could not create the example tables: ' . $e->getMessage() . "\n");
    exit(1);
}

echo "Example tables are ready in database '{$name}'.\n";
