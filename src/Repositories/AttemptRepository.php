<?php
declare(strict_types=1);

namespace Boyd\LoginLibrary\Repositories;

use Boyd\LoginLibrary\Database\Database;

class AttemptRepository
{
    public function __construct(
        private Database $database
    ) {}

    /**
     * Record a failed login attempt for a specific IP and Username.
     */
    public function recordAttempt(string $ipAddress, string $username): void
    {
        $sql = "INSERT INTO login_attempts (ip_address, username, attempt_time) VALUES (:ip, :user, :time)";
        $stmt = $this->database->getConnection()->prepare($sql);
        $stmt->execute([
            'ip' => $ipAddress,
            'user' => $username,
            'time' => time()
        ]);
    }

    /**
     * Count the number of failed attempts for an IP address within the given timeframe.
     */
    public function countRecentAttempts(string $ipAddress, int $lockoutTimeSeconds): int
    {
        $timeThreshold = time() - $lockoutTimeSeconds;

        $sql = "SELECT COUNT(*) FROM login_attempts WHERE ip_address = :ip AND attempt_time > :time";
        $stmt = $this->database->getConnection()->prepare($sql);
        $stmt->execute([
            'ip' => $ipAddress,
            'time' => $timeThreshold
        ]);

        return (int)$stmt->fetchColumn();
    }

    /**
     * Clear all failed attempts for an IP address (e.g., after a successful login).
     */
    public function clearAttempts(string $ipAddress): void
    {
        $sql = "DELETE FROM login_attempts WHERE ip_address = :ip";
        $stmt = $this->database->getConnection()->prepare($sql);
        $stmt->execute(['ip' => $ipAddress]);
    }

    /**
     * Clean up old attempts from the database to keep the table small.
     */
    public function cleanupOldAttempts(int $lockoutTimeSeconds): void
    {
        $timeThreshold = time() - $lockoutTimeSeconds;
        $sql = "DELETE FROM login_attempts WHERE attempt_time < :time";
        $stmt = $this->database->getConnection()->prepare($sql);
        $stmt->execute(['time' => $timeThreshold]);
    }
}
