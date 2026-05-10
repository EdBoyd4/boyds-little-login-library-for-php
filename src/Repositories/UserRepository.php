<?php
declare(strict_types=1);

namespace Boyd\LoginLibrary\Repositories;

use Boyd\LoginLibrary\Config\LoginConfig;
use Boyd\LoginLibrary\Database\Database;
use Boyd\LoginLibrary\Models\User;
use PDO;

class UserRepository
{
    public function __construct(
        private Database $database,
        private LoginConfig $config
    ) {}

    public function findByUsername(string $username): ?User
    {
        $tableUsers = $this->config->getTableUsers();
        $colUserId = $this->config->getColUserId();
        $colUsername = $this->config->getColUsername();
        $colPassword = $this->config->getColPassword();

        // 1. Fetch the core user record
        $sql = sprintf(
            "SELECT %s, %s, %s FROM %s WHERE %s = :username LIMIT 1",
            $colUserId, $colUsername, $colPassword, $tableUsers, $colUsername
        );

        $stmt = $this->database->getConnection()->prepare($sql);
        $stmt->execute(['username' => $username]);
        $userData = $stmt->fetch();

        if (!$userData) {
            return null;
        }

        $userId = (int)$userData[$colUserId];

        // 2. Fetch the user's active roles via the linking table
        $tableRoles = $this->config->getTableRoles();
        $tableUserRoles = $this->config->getTableUserRoles();
        $colRoleId = $this->config->getColRoleId();
        $colRoleName = $this->config->getColRoleName();

        $roleSql = sprintf(
            "SELECT r.%s 
             FROM %s r
             JOIN %s ur ON r.%s = ur.%s
             WHERE ur.%s = :user_id",
            $colRoleName,
            $tableRoles,
            $tableUserRoles, $colRoleId, $colRoleId,
            $colUserId
        );

        $roleStmt = $this->database->getConnection()->prepare($roleSql);
        $roleStmt->execute(['user_id' => $userId]);
        
        $roles = [];
        while ($roleRow = $roleStmt->fetch()) {
            $roles[] = $roleRow[$colRoleName];
        }

        return new User(
            id: $userId,
            username: $userData[$colUsername],
            passwordHash: trim((string)$userData[$colPassword]),
            roles: $roles
        );
    }
}
