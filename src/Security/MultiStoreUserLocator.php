<?php
declare(strict_types=1);

namespace Boyd\LoginLibrary\Security;

use Boyd\LoginLibrary\Contracts\UserFinderInterface;

/**
 * Finds which of several independent identity stores, if any, already holds
 * a given username. For a consumer running more than one identity store
 * against separate databases -- e.g. a manually-managed store alongside a
 * self-service one -- this is how code enforces that a username is never
 * registered in more than one of them at a time.
 *
 * Stores are checked in the order given, so list the one that should win
 * precedence first.
 */
final class MultiStoreUserLocator
{
    /** @param array<string, UserFinderInterface> $repositories store label => repository */
    public function __construct(private array $repositories) {}

    /** True if $username is not registered in any wired store. */
    public function isAvailableEverywhere(string $username): bool
    {
        return $this->locate($username) === null;
    }

    /** The label of the store $username is already registered in, or null if it's free everywhere. */
    public function locate(string $username): ?string
    {
        foreach ($this->repositories as $storeLabel => $repository) {
            if ($repository->findByUsername($username) !== null) {
                return $storeLabel;
            }
        }
        return null;
    }
}
