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
        $table = $this->config->getDbTableUsers();
        $colId = $this->config->getColUserId();
        $colUser = $this->config->getColUsername();
        $colPass = $this->config->getColPassword();
        $colRole = $this->config->getColRole();

        $sql = sprintf(
            "SELECT %s, %s, %s, %s FROM %s WHERE %s = :username LIMIT 1",
            $colId, $colUser, $colPass, $colRole, $table, $colUser
        );

        $stmt = $this->database->getConnection()->prepare($sql);

        $stmt->execute(['username' => $username]);
        $row = $stmt->fetch();

        if (!$row) {
            return null;
        }

        return new User(
            id: (int)$row[$colId],
            username: $row[$colUser],
            passwordHash: $row[$colPass],
            role: $row[$colRole]
        );
    }
}
