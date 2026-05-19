<?php
declare(strict_types=1);

namespace Boyd\LoginLibrary\Repositories;

use Boyd\LoginLibrary\Database\Database;

class SecurityAuditRepository
{
    public function __construct(
        private Database $database
    ) {}

    /**
     * Log a security event to the audit table.
     *
     * @param string $eventType e.g., 'login_success', 'lockout', 'discrepancy', 'logout'
     * @param string $ipAddress The IP address of the user triggering the event
     * @param string|null $username The username involved, if known
     * @param string|null $details Additional context as a JSON string or plain text
     */
    public function logEvent(string $eventType, string $ipAddress, ?string $username = null, ?string $details = null): void
    {
        $sql = "INSERT INTO security_audit_logs (event_type, ip_address, username_involved, details) 
                VALUES (:type, :ip, :user, :details)";
        
        $stmt = $this->database->getConnection()->prepare($sql);
        $stmt->execute([
            'type' => $eventType,
            'ip' => $ipAddress,
            'user' => $username,
            'details' => $details
        ]);
    }
}
