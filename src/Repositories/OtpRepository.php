<?php
declare(strict_types=1);

namespace Boyd\LoginLibrary\Repositories;

use Boyd\LoginLibrary\Contracts\OtpRepositoryInterface;
use Boyd\LoginLibrary\Database\Database;
use PDO;

/**
 * MySQL/MariaDB persistence for one-time login codes. One row per issued code;
 * the newest un-consumed, un-expired row for an identifier is the live
 * challenge. Only keyed HMAC digests of codes are stored (see OtpManager).
 */
class OtpRepository implements OtpRepositoryInterface
{
    public function __construct(
        private Database $database,
        private string $table = 'otp_challenges'
    ) {}

    public function create(string $identifier, string $codeHash, int $expiresAt): int
    {
        $pdo = $this->database->getConnection();
        $stmt = $pdo->prepare(
            "INSERT INTO {$this->table} (identifier, code_hash, expires_at, attempts, created_at)
             VALUES (:identifier, :code_hash, :expires_at, 0, :created_at)"
        );
        $stmt->execute([
            'identifier' => $identifier,
            'code_hash'  => $codeHash,
            'expires_at' => $expiresAt,
            'created_at' => time(),
        ]);

        return (int) $pdo->lastInsertId();
    }

    public function findLatestActive(string $identifier): ?array
    {
        $stmt = $this->database->getConnection()->prepare(
            "SELECT id, identifier, code_hash, expires_at, attempts, consumed_at, created_at
             FROM {$this->table}
             WHERE identifier = :identifier
               AND consumed_at IS NULL
               AND expires_at > :now
             ORDER BY id DESC
             LIMIT 1"
        );
        $stmt->execute(['identifier' => $identifier, 'now' => time()]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($row === false) {
            return null;
        }

        // Normalise types (PDO returns strings without native typing).
        return [
            'id'          => (int) $row['id'],
            'identifier'  => (string) $row['identifier'],
            'code_hash'   => (string) $row['code_hash'],
            'expires_at'  => (int) $row['expires_at'],
            'attempts'    => (int) $row['attempts'],
            'consumed_at' => $row['consumed_at'] === null ? null : (int) $row['consumed_at'],
            'created_at'  => (int) $row['created_at'],
        ];
    }

    public function incrementAttempts(int $id): void
    {
        $stmt = $this->database->getConnection()->prepare(
            "UPDATE {$this->table} SET attempts = attempts + 1 WHERE id = :id"
        );
        $stmt->execute(['id' => $id]);
    }

    public function consume(int $id): bool
    {
        // The `consumed_at IS NULL` guard makes this the single point of
        // serialisation: only one concurrent verifier flips the row and sees
        // rowCount() === 1.
        $stmt = $this->database->getConnection()->prepare(
            "UPDATE {$this->table} SET consumed_at = :now WHERE id = :id AND consumed_at IS NULL"
        );
        $stmt->execute(['now' => time(), 'id' => $id]);

        return $stmt->rowCount() === 1;
    }

    public function countRecentFor(string $identifier, int $sinceUnixTs): int
    {
        $stmt = $this->database->getConnection()->prepare(
            "SELECT COUNT(*) FROM {$this->table}
             WHERE identifier = :identifier AND created_at >= :since"
        );
        $stmt->execute(['identifier' => $identifier, 'since' => $sinceUnixTs]);

        return (int) $stmt->fetchColumn();
    }

    public function supersedeActiveFor(string $identifier): void
    {
        $stmt = $this->database->getConnection()->prepare(
            "UPDATE {$this->table} SET consumed_at = :now
             WHERE identifier = :identifier AND consumed_at IS NULL"
        );
        $stmt->execute(['now' => time(), 'identifier' => $identifier]);
    }

    public function deleteForIdentifier(string $identifier): void
    {
        $stmt = $this->database->getConnection()->prepare(
            "DELETE FROM {$this->table} WHERE identifier = :identifier"
        );
        $stmt->execute(['identifier' => $identifier]);
    }

    public function cleanupExpired(int $olderThanUnixTs): void
    {
        $stmt = $this->database->getConnection()->prepare(
            "DELETE FROM {$this->table} WHERE expires_at < :cutoff"
        );
        $stmt->execute(['cutoff' => $olderThanUnixTs]);
    }
}
