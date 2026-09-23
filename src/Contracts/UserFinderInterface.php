<?php
declare(strict_types=1);

namespace Boyd\LoginLibrary\Contracts;

use Boyd\LoginLibrary\Models\User;

/**
 * The one thing MultiStoreUserLocator needs from an identity store: look a
 * user up by username. Implemented by both UserRepository (password + roles
 * tables) and PasswordlessUserRepository (email-only), which have otherwise
 * different shapes.
 */
interface UserFinderInterface
{
    public function findByUsername(string $username): ?User;
}
