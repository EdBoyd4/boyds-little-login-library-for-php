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
        private int $sessionTimeoutSeconds = 1800,
        private string $loginRoute = '/login.php',
        private string $unauthorizedRoute = '/unauthorized.php',

        // Security Throttling
        private int $maxLoginAttempts = 5,
        private int $lockoutTimeMinutes = 15
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
}
