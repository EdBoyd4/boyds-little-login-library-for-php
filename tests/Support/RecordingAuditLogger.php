<?php
declare(strict_types=1);

namespace Boyd\LoginLibrary\Tests\Support;

use Boyd\LoginLibrary\Contracts\SecurityAuditLoggerInterface;

final class RecordingAuditLogger implements SecurityAuditLoggerInterface
{
    /** @var array<int, array{event:string, ip:string, user:?string, details:?string}> */
    public array $events = [];

    public function logEvent(string $eventType, string $ipAddress, ?string $username = null, ?string $details = null): void
    {
        $this->events[] = ['event' => $eventType, 'ip' => $ipAddress, 'user' => $username, 'details' => $details];
    }

    /** @return string[] */
    public function eventTypes(): array
    {
        return array_map(static fn (array $e) => $e['event'], $this->events);
    }

    public function has(string $eventType): bool
    {
        return in_array($eventType, $this->eventTypes(), true);
    }
}
