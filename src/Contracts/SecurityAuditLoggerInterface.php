<?php
declare(strict_types=1);

namespace Boyd\LoginLibrary\Contracts;

/**
 * Sink for security-relevant events. SecurityAuditRepository is the database
 * implementation; the interface lets OtpManager (and tests) depend on the
 * behaviour rather than the storage.
 */
interface SecurityAuditLoggerInterface
{
    /**
     * @param string      $eventType e.g. 'otp_issued', 'otp_verified', 'otp_failed'
     * @param string      $ipAddress source IP, or '0.0.0.0' when unknown
     * @param string|null $username  the address / username involved, if known
     * @param string|null $details   free-text or JSON context
     */
    public function logEvent(string $eventType, string $ipAddress, ?string $username = null, ?string $details = null): void;
}
