<?php
declare(strict_types=1);

namespace Boyd\LoginLibrary\Repositories;

use Boyd\LoginLibrary\Config\LoginConfig;
use Boyd\LoginLibrary\Contracts\UserFinderInterface;
use Boyd\LoginLibrary\Database\Database;
use Boyd\LoginLibrary\Models\User;
use InvalidArgumentException;
use PDO;

/**
 * Identity store for users who sign in only by emailed one-time code. There is
 * no password and no roles table: a row is just the verified email address
 * (users.user_name, lower-cased) plus timestamps, and every user this store
 * returns carries the single fixed role it was constructed with.
 *
 * Expected table (names from LoginConfig's tableUsers / colUserId /
 * colUsername):
 *
 *   users (user_id INT AUTO_INCREMENT PRIMARY KEY,
 *          user_name VARCHAR(255) NOT NULL UNIQUE,
 *          created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
 *          updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP)
 */
class PasswordlessUserRepository implements UserFinderInterface
{
    public function __construct(
        private Database $database,
        private LoginConfig $config,
        private string $fixedRole
    ) {}

    public function findByUsername(string $username): ?User
    {
        $table = $this->identifier($this->config->getTableUsers());
        $colId = $this->identifier($this->config->getColUserId());
        $colName = $this->identifier($this->config->getColUsername());

        $stmt = $this->database->getConnection()->prepare(
            sprintf('SELECT %s, %s FROM %s WHERE %s = :username LIMIT 1', $colId, $colName, $table, $colName)
        );
        $stmt->execute(['username' => $this->normalize($username)]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row === false ? null : $this->toUser($row, $colId, $colName);
    }

    /**
     * Registers the address, or returns the existing user if it is already
     * registered (the UNIQUE key makes a concurrent double-registration
     * resolve to one row rather than an error).
     */
    public function findOrRegister(string $email): User
    {
        $email = $this->normalize($email);
        $existing = $this->findByUsername($email);
        if ($existing !== null) {
            return $existing;
        }

        $table = $this->identifier($this->config->getTableUsers());
        $colName = $this->identifier($this->config->getColUsername());

        $this->database->getConnection()
            ->prepare(sprintf('INSERT IGNORE INTO %s (%s) VALUES (:username)', $table, $colName))
            ->execute(['username' => $email]);

        return $this->findByUsername($email)
            ?? throw new \RuntimeException('Could not load the newly registered account for ' . $email);
    }

    private function normalize(string $email): string
    {
        return strtolower(trim($email));
    }

    private function toUser(array $row, string $colId, string $colName): User
    {
        return new User(
            id: (int) $row[$colId],
            username: (string) $row[$colName],
            passwordHash: '',
            roles: [$this->fixedRole]
        );
    }

    /** Table/column names come from LoginConfig, not request input; same guard as UserRepository. */
    private function identifier(string $name): string
    {
        if (!preg_match('/^[A-Za-z_][A-Za-z0-9_]*$/', $name)) {
            throw new InvalidArgumentException("Invalid SQL identifier: {$name}");
        }
        return $name;
    }
}
