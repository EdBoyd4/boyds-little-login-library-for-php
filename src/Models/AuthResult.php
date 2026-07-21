<?php
declare(strict_types=1);

namespace Boyd\LoginLibrary\Models;

class AuthResult
{
    public function __construct(
        public readonly bool $success,
        public readonly ?string $error = null,
        public readonly ?string $redirectUrl = null,
        public readonly ?string $csrfToken = null
    ) {}
}
