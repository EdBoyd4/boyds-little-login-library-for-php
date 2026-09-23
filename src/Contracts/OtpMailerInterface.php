<?php
declare(strict_types=1);

namespace Boyd\LoginLibrary\Contracts;

/**
 * The library issues and verifies one-time codes but does not send email -- it
 * has no mail dependency. The host application supplies an implementation of
 * this interface (e.g. a thin PHPMailer wrapper) when constructing OtpManager.
 */
interface OtpMailerInterface
{
    /**
     * Deliver a one-time login code to the given address.
     *
     * Implementations SHOULD throw on hard delivery failure so the caller can
     * surface a generic "could not send" message; they MUST NOT echo the code
     * anywhere but the outgoing message.
     *
     * @param string $toEmail    Recipient address (as the user typed it).
     * @param string $code       The plaintext code to include in the message.
     * @param int    $ttlSeconds How long the code remains valid, for message copy.
     */
    public function sendOtp(string $toEmail, string $code, int $ttlSeconds): void;
}
