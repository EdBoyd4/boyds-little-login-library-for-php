<?php
declare(strict_types=1);

namespace Boyd\LoginLibrary\Repositories;

use Boyd\LoginLibrary\Config\LoginConfig;
use Boyd\LoginLibrary\Database\Database;
use Boyd\LoginLibrary\Models\User;
use Exception;
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
    public function getAllRoles(): array
    {
        $tableRoles = $this->config->getTableRoles();
        $colRoleId = $this->config->getColRoleId();
        $colRoleName = $this->config->getColRoleName();

        $sql = sprintf("SELECT %s, %s FROM %s ORDER BY %s", $colRoleId, $colRoleName, $tableRoles, $colRoleName);
        $stmt = $this->database->getConnection()->query($sql);
        
        $roles = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $roles[$row[$colRoleId]] = $row[$colRoleName];
        }
        return $roles;
    }

    public function addUser(string $username, string $password, array $roleIds = []): void
    {
        if ($this->findByUsername($username) !== null) {
            throw new Exception("Username already exists.");
        }

        $tableUsers = $this->config->getTableUsers();
        $colUsername = $this->config->getColUsername();
        $colPassword = $this->config->getColPassword();

        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

        $sql = sprintf(
            "INSERT INTO %s (%s, %s) VALUES (:username, :password)",
            $tableUsers, $colUsername, $colPassword
        );

        $pdo = $this->database->getConnection();
        $pdo->beginTransaction();

        try {
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                'username' => $username,
                'password' => $hashedPassword
            ]);

            $userId = (int)$pdo->lastInsertId();

            if (!empty($roleIds)) {
                $tableUserRoles = $this->config->getTableUserRoles();
                $colUserId = $this->config->getColUserId();
                $colRoleId = $this->config->getColRoleId();

                $roleSql = sprintf(
                    "INSERT INTO %s (%s, %s) VALUES (:user_id, :role_id)",
                    $tableUserRoles, $colUserId, $colRoleId
                );
                $roleStmt = $pdo->prepare($roleSql);
                
                foreach ($roleIds as $roleId) {
                    $roleStmt->execute([
                        'user_id' => $userId,
                        'role_id' => (int)$roleId
                    ]);
                }
            }

            $pdo->commit();
        } catch (Exception $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    /**
     * @return array<int, array{id: int, username: string}>
     */
    public function getAllUsers(): array
    {
        $tableUsers = $this->config->getTableUsers();
        $colUserId = $this->config->getColUserId();
        $colUsername = $this->config->getColUsername();

        $sql = sprintf(
            "SELECT %s, %s FROM %s ORDER BY %s ASC",
            $colUserId, $colUsername, $tableUsers, $colUsername
        );

        $stmt = $this->database->getConnection()->query($sql);
        
        $users = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $users[] = [
                'id' => (int)$row[$colUserId],
                'username' => $row[$colUsername]
            ];
        }
        return $users;
    }

    public function removeUser(int $userId): void
    {
        $pdo = $this->database->getConnection();
        $pdo->beginTransaction();

        try {
            $tableUserRoles = $this->config->getTableUserRoles();
            $colUserId = $this->config->getColUserId();
            $tableUsers = $this->config->getTableUsers();

            // Delete user's roles
            $roleSql = sprintf("DELETE FROM %s WHERE %s = :user_id", $tableUserRoles, $colUserId);
            $roleStmt = $pdo->prepare($roleSql);
            $roleStmt->execute(['user_id' => $userId]);

            // Delete user
            $userSql = sprintf("DELETE FROM %s WHERE %s = :user_id", $tableUsers, $colUserId);
            $userStmt = $pdo->prepare($userSql);
            $userStmt->execute(['user_id' => $userId]);

            $pdo->commit();
        } catch (Exception $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    public function updatePassword(int $userId, string $newPassword): void
    {
        $tableUsers = $this->config->getTableUsers();
        $colUserId = $this->config->getColUserId();
        $colPassword = $this->config->getColPassword();

        $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);

        $sql = sprintf(
            "UPDATE %s SET %s = :password WHERE %s = :user_id",
            $tableUsers, $colPassword, $colUserId
        );

        $stmt = $this->database->getConnection()->prepare($sql);
        $stmt->execute([
            'password' => $hashedPassword,
            'user_id' => $userId
        ]);
    }
}
