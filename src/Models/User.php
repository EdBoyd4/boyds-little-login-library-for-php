<?php
declare(strict_types=1);

namespace Boyd\LoginLibrary\Models;

class User
{
    /**
     * @param int $id
     * @param string $username
     * @param string $passwordHash
     * @param string[] $roles
     */
    public function __construct(
        private int $id,
        private string $username,
        private string $passwordHash,
        private array $roles = []
    ) {}

    public function getId(): int { return $this->id; }
    public function getUsername(): string { return $this->username; }
    public function getPasswordHash(): string { return $this->passwordHash; }
    
    /**
     * @return string[]
     */
    public function getRoles(): array { return $this->roles; }
}
