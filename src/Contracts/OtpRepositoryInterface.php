<?php
declare(strict_types=1);

namespace Boyd\LoginLibrary\Contracts;

/**
 * Persistence contract for one-time login codes. Extracted so OtpManager can be
 * unit-tested against an in-memory fake and so an application can supply its own
 * storage (Redis, DynamoDB, ...) without touching the manager -- that's the only
 * axis this interface is generic over. `identifier` is always a normalized email
 * address (lower-cased and validated by OtpManager before it ever reaches the
 * repository; see OtpManager's class docblock) -- by policy, this module handles
 * email only, not an arbitrary identifier type. Timestamps are unix seconds.
 */
interface OtpRepositoryInterface
{
    public function create(string $identifier, string $codeHash, int $expiresAt): int;

    /**
     * The newest un-consumed, un-expired challenge for the identifier, or null.
     *
     * @return array{id:int,identifier:string,code_hash:string,expires_at:int,attempts:int,consumed_at:?int,created_at:int}|null
     */
    public function findLatestActive(string $identifier): ?array;

    public function incrementAttempts(int $id): void;

    /**
     * Atomically mark the challenge consumed. Returns true only for the caller
     * that actually flipped it from un-consumed to consumed, so a double-submit
     * or a race produces exactly one winner.
     */
    public function consume(int $id): bool;

    /** Count challenges created for the identifier at or after $sinceUnixTs. */
    public function countRecentFor(string $identifier, int $sinceUnixTs): int;

    /**
     * Invalidate any still-active challenge(s) for the identifier so only the
     * next-issued one is live. Rows are kept (not deleted) so countRecentFor()
     * and the audit trail still see them.
     */
    public function supersedeActiveFor(string $identifier): void;

    /** Hard-delete every challenge for the identifier (e.g. on account removal). */
    public function deleteForIdentifier(string $identifier): void;

    /** Housekeeping: delete challenges that expired before $olderThanUnixTs. */
    public function cleanupExpired(int $olderThanUnixTs): void;
}
