<?php
declare(strict_types=1);

namespace Boyd\LoginLibrary\Tests\Support;

use Boyd\LoginLibrary\Contracts\OtpMailerInterface;
use RuntimeException;

/**
 * Records every sendOtp() call. Set $throwOnSend to simulate a mail failure.
 */
final class SpyOtpMailer implements OtpMailerInterface
{
    /** @var array<int, array{email:string, code:string, ttl:int}> */
    public array $sent = [];

    public bool $throwOnSend = false;

    public function sendOtp(string $toEmail, string $code, int $ttlSeconds): void
    {
        if ($this->throwOnSend) {
            throw new RuntimeException('simulated mail failure');
        }
        $this->sent[] = ['email' => $toEmail, 'code' => $code, 'ttl' => $ttlSeconds];
    }

    public function lastCode(): ?string
    {
        return $this->sent === [] ? null : $this->sent[array_key_last($this->sent)]['code'];
    }

    public function count(): int
    {
        return count($this->sent);
    }
}
