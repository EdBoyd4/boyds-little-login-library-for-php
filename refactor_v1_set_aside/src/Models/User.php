<?php
declare(strict_types=1);

namespace Boyd\LoginLibrary\Models;

class User
{
    public function __construct(
        private int $id,
        private string $username,
        private string $passwordHash,
        private string|int $role // Depending on how roles are stored
    ) {}

    public function getId(): int { return $this->id; }
    public function getUsername(): string { return $this->username; }
    public function getPasswordHash(): string { return $this->passwordHash; }
    public function getRole(): string|int { return $this->role; }
}
