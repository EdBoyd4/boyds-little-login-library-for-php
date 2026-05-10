<?php
declare(strict_types=1);

namespace Boyd\LoginLibrary\Config;

class LoginConfig
{
    public function __construct(
        private string $dbHost,
        private string $dbName,
        private string $dbUser,
        private string $dbPass,
        private string $dbTableUsers = 'users',
        private string $colUserId = 'uid',
        private string $colUsername = 'user_name',
        private string $colPassword = 'password',
        private string $colRole = 'user_role',
        private string $sessionName = 'BoydLoginSession',
        private int $sessionTimeoutSeconds = 1800,
        private string $loginRoute = '/login.php',
        private string $unauthorizedRoute = '/unauthorized.php'
    ) {}

    public function getDbHost(): string { return $this->dbHost; }
    public function getDbName(): string { return $this->dbName; }
    public function getDbUser(): string { return $this->dbUser; }
    public function getDbPass(): string { return $this->dbPass; }
    public function getDbTableUsers(): string { return $this->dbTableUsers; }
    public function getColUserId(): string { return $this->colUserId; }
    public function getColUsername(): string { return $this->colUsername; }
    public function getColPassword(): string { return $this->colPassword; }
    public function getColRole(): string { return $this->colRole; }
    public function getSessionName(): string { return $this->sessionName; }
    public function getSessionTimeoutSeconds(): int { return $this->sessionTimeoutSeconds; }
    public function getLoginRoute(): string { return $this->loginRoute; }
    public function getUnauthorizedRoute(): string { return $this->unauthorizedRoute; }
}
