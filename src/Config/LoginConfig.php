<?php
declare(strict_types=1);

namespace Boyd\LoginLibrary\Config;

class LoginConfig
{
    public function __construct(
        // Database credentials
        private string $dbHost,
        private string $dbName,
        private string $dbUser,
        private string $dbPass,
        
        // Users Table
        private string $tableUsers = 'users',
        private string $colUserId = 'user_id',
        private string $colUsername = 'user_name',
        private string $colPassword = 'password',
        
        // Roles Table
        private string $tableRoles = 'roles',
        private string $colRoleId = 'role_id',
        private string $colRoleName = 'role',
        
        // User Roles Map Table
        private string $tableUserRoles = 'user_roles',
        
        // Security Settings
        private string $sessionName = 'BoydLoginSession',
        // Idle timeout for password-authenticated sessions (4 hours by default).
        // Sessions established by one-time email code use otpSessionTimeoutSeconds
        // instead -- see below.
        private int $sessionTimeoutSeconds = 14400,
        private string $loginRoute = '/login.php',
        private string $unauthorizedRoute = '/unauthorized.php',

        // Security Throttling
        private int $maxLoginAttempts = 5,
        private int $lockoutTimeMinutes = 15,

        // Role permitted to manage users / change auth settings. UserController
        // also takes this as its own constructor arg, defaulting the same way;
        // this gives a single place to set it. Matches the lower-case
        // 'system_manager' that database/create_example_database.php actually seeds -- earlier,
        // UserController's default and every example/README reference used
        // 'System_Manager' (capitalized), which never matched the seeded role
        // and silently broke requireRole() checks against a fresh install.
        // Fixed everywhere 2026-09-14.
        private string $userManagerRole = 'system_manager',

        // Bounds for the runtime-adjustable session length (see SettingsController
        // + SessionSettingsView). The effective timeout is clamped to this range.
        private int $sessionTimeoutSecondsMin = 300,
        private int $sessionTimeoutSecondsMax = 86400,

        // One-time-code (email OTP / passwordless) settings.
        private int $otpCodeLength = 6,
        private int $otpTtlSeconds = 600,
        private int $otpMaxAttempts = 5,
        private int $otpResendCooldownSeconds = 60,
        private int $otpMaxSendsPerHour = 5,

        // Table name overrides for the additive features.
        private string $tableAuthSettings = 'auth_settings',
        private string $tableOtpChallenges = 'otp_challenges',

        // Label for the identity store this config's users/roles tables live
        // in, e.g. 'user_access' vs 'otp_users' for a consumer running two
        // independent identity databases. AuthManager stamps it into the
        // session (see currentUserSource()) so code touching data outside the
        // auth tables -- which user_id alone can't disambiguate once two
        // separate user tables exist -- knows which store a session's user_id
        // came from. Null (the default) for every existing single-database
        // consumer; this is purely additive.
        private ?string $identitySource = null,

        // Idle timeout for sessions established by a verified one-time email
        // code (AuthManager::establishPasswordlessSession stamps it onto the
        // session), 30 minutes by default -- shorter than a password session
        // because an emailed code is a weaker, easier-to-obtain credential.
        private int $otpSessionTimeoutSeconds = 1800,

        // Secret used to key the HMAC that OtpManager stores in place of each
        // code. Must be a random value of at least 32 characters kept OUTSIDE
        // the database (e.g. in .env), or a database-only leak would expose
        // live codes. Empty by default, which OtpManager rejects -- a
        // consumer that never uses OTP never needs to set it.
        private string $otpHmacKey = ''
    ) {}

    public function getDbHost(): string { return $this->dbHost; }
    public function getDbName(): string { return $this->dbName; }
    public function getDbUser(): string { return $this->dbUser; }
    public function getDbPass(): string { return $this->dbPass; }
    
    public function getTableUsers(): string { return $this->tableUsers; }
    public function getColUserId(): string { return $this->colUserId; }
    public function getColUsername(): string { return $this->colUsername; }
    public function getColPassword(): string { return $this->colPassword; }
    
    public function getTableRoles(): string { return $this->tableRoles; }
    public function getColRoleId(): string { return $this->colRoleId; }
    public function getColRoleName(): string { return $this->colRoleName; }
    
    public function getTableUserRoles(): string { return $this->tableUserRoles; }

    public function getSessionName(): string { return $this->sessionName; }
    public function getSessionTimeoutSeconds(): int { return $this->sessionTimeoutSeconds; }
    public function getLoginRoute(): string { return $this->loginRoute; }
    public function getUnauthorizedRoute(): string { return $this->unauthorizedRoute; }

    public function getMaxLoginAttempts(): int { return $this->maxLoginAttempts; }
    public function getLockoutTimeSeconds(): int { return $this->lockoutTimeMinutes * 60; }

    public function getUserManagerRole(): string { return $this->userManagerRole; }

    public function getSessionTimeoutSecondsMin(): int { return $this->sessionTimeoutSecondsMin; }
    public function getSessionTimeoutSecondsMax(): int { return $this->sessionTimeoutSecondsMax; }

    public function getOtpCodeLength(): int { return $this->otpCodeLength; }
    public function getOtpTtlSeconds(): int { return $this->otpTtlSeconds; }
    public function getOtpMaxAttempts(): int { return $this->otpMaxAttempts; }
    public function getOtpResendCooldownSeconds(): int { return $this->otpResendCooldownSeconds; }
    public function getOtpMaxSendsPerHour(): int { return $this->otpMaxSendsPerHour; }

    public function getTableAuthSettings(): string { return $this->tableAuthSettings; }
    public function getTableOtpChallenges(): string { return $this->tableOtpChallenges; }

    public function getIdentitySource(): ?string { return $this->identitySource; }

    public function getOtpSessionTimeoutSeconds(): int { return $this->otpSessionTimeoutSeconds; }
    public function getOtpHmacKey(): string { return $this->otpHmacKey; }
}
