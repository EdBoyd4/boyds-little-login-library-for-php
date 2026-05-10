<?php
declare(strict_types=1);

namespace Boyd\LoginLibrary\Database;

use Boyd\LoginLibrary\Config\LoginConfig;
use PDO;
use PDOException;

class Database
{
    private ?PDO $connection = null;

    public function __construct(private LoginConfig $config) {}

    public function getConnection(): PDO
    {
        if ($this->connection === null) {
            $dsn = sprintf(
                "mysql:host=%s;dbname=%s;charset=utf8mb4",
                $this->config->getDbHost(),
                $this->config->getDbName()
            );

            try {
                $this->connection = new PDO($dsn, $this->config->getDbUser(), $this->config->getDbPass(), [
                    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES   => false,
                ]);
            } catch (PDOException $e) {
                // You might want to log this error using a proper logger instead of error_log in production
                error_log("Connection failed: " . $e->getMessage());
                throw new \Exception("Database connection failed.");
            }
        }

        return $this->connection;
    }
}
