<?php
declare(strict_types=1);

namespace Boyd\LoginLibrary\Repositories;

use Boyd\LoginLibrary\Database\Database;
use PDOException;

/**
 * Key/value store for runtime-tunable auth settings (currently just the session
 * length). Reads are defensive: if the host application has not yet run the
 * migration that creates the table, get()/all() log and fall back to null/[]
 * rather than fataling, so a shared library consumer that never opted in keeps
 * working unchanged.
 */
class SettingsRepository
{
    public function __construct(
        private Database $database,
        private string $table = 'auth_settings'
    ) {}

    public function get(string $key): ?string
    {
        try {
            $stmt = $this->database->getConnection()->prepare(
                "SELECT setting_value FROM {$this->table} WHERE setting_key = :key LIMIT 1"
            );
            $stmt->execute(['key' => $key]);
            $value = $stmt->fetchColumn();
            return $value === false ? null : (string)$value;
        } catch (PDOException $e) {
            error_log('SettingsRepository::get(' . $key . ') failed: ' . $e->getMessage());
            return null;
        }
    }

    public function set(string $key, string $value): void
    {
        $sql = "INSERT INTO {$this->table} (setting_key, setting_value)
                VALUES (:key, :value)
                ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)";
        $stmt = $this->database->getConnection()->prepare($sql);
        $stmt->execute(['key' => $key, 'value' => $value]);
    }

    /**
     * @return array<string, string>
     */
    public function all(): array
    {
        try {
            $stmt = $this->database->getConnection()->query(
                "SELECT setting_key, setting_value FROM {$this->table}"
            );
            $out = [];
            foreach ($stmt as $row) {
                $out[(string)$row['setting_key']] = (string)$row['setting_value'];
            }
            return $out;
        } catch (PDOException $e) {
            error_log('SettingsRepository::all() failed: ' . $e->getMessage());
            return [];
        }
    }
}
