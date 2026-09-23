<?php
declare(strict_types=1);

namespace Boyd\LoginLibrary\Tests\Support;

use Boyd\LoginLibrary\Contracts\OtpRepositoryInterface;

/**
 * Array-backed OtpRepositoryInterface for unit tests. Mirrors the SQL
 * semantics of the real repository (single active row = newest un-consumed,
 * un-expired; consume() is a one-winner flip).
 */
class InMemoryOtpRepository implements OtpRepositoryInterface
{
    /** @var array<int, array{id:int,identifier:string,code_hash:string,expires_at:int,attempts:int,consumed_at:?int,created_at:int}> */
    public array $rows = [];
    private int $nextId = 1;

    public function create(string $identifier, string $codeHash, int $expiresAt): int
    {
        $id = $this->nextId++;
        $this->rows[$id] = [
            'id'          => $id,
            'identifier'  => $identifier,
            'code_hash'   => $codeHash,
            'expires_at'  => $expiresAt,
            'attempts'    => 0,
            'consumed_at' => null,
            'created_at'  => time(),
        ];
        return $id;
    }

    public function findLatestActive(string $identifier): ?array
    {
        $now = time();
        $matches = array_filter(
            $this->rows,
            static fn (array $r) => $r['identifier'] === $identifier
                && $r['consumed_at'] === null
                && $r['expires_at'] > $now
        );
        if ($matches === []) {
            return null;
        }
        usort($matches, static fn (array $a, array $b) => $b['id'] <=> $a['id']);
        return $matches[0];
    }

    public function incrementAttempts(int $id): void
    {
        if (isset($this->rows[$id])) {
            $this->rows[$id]['attempts']++;
        }
    }

    public function consume(int $id): bool
    {
        if (isset($this->rows[$id]) && $this->rows[$id]['consumed_at'] === null) {
            $this->rows[$id]['consumed_at'] = time();
            return true;
        }
        return false;
    }

    public function countRecentFor(string $identifier, int $sinceUnixTs): int
    {
        return count(array_filter(
            $this->rows,
            static fn (array $r) => $r['identifier'] === $identifier && $r['created_at'] >= $sinceUnixTs
        ));
    }

    public function supersedeActiveFor(string $identifier): void
    {
        foreach ($this->rows as $id => $r) {
            if ($r['identifier'] === $identifier && $r['consumed_at'] === null) {
                $this->rows[$id]['consumed_at'] = time();
            }
        }
    }

    public function deleteForIdentifier(string $identifier): void
    {
        foreach ($this->rows as $id => $r) {
            if ($r['identifier'] === $identifier) {
                unset($this->rows[$id]);
            }
        }
    }

    public function cleanupExpired(int $olderThanUnixTs): void
    {
        foreach ($this->rows as $id => $r) {
            if ($r['expires_at'] < $olderThanUnixTs) {
                unset($this->rows[$id]);
            }
        }
    }

    // --- test helpers -------------------------------------------------------

    /** Force every stored challenge to look already-expired. */
    public function forceExpireAll(): void
    {
        foreach ($this->rows as $id => $_r) {
            $this->rows[$id]['expires_at'] = time() - 1;
        }
    }

    public function activeCountFor(string $identifier): int
    {
        $now = time();
        return count(array_filter(
            $this->rows,
            static fn (array $r) => $r['identifier'] === $identifier
                && $r['consumed_at'] === null
                && $r['expires_at'] > $now
        ));
    }
}
