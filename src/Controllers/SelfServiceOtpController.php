<?php
declare(strict_types=1);

namespace Boyd\LoginLibrary\Controllers;

use Boyd\LoginLibrary\Models\AuthResult;
use Boyd\LoginLibrary\Repositories\PasswordlessUserRepository;
use Boyd\LoginLibrary\Security\AuthManager;
use Boyd\LoginLibrary\Security\OtpManager;
use Boyd\LoginLibrary\Security\OtpVerificationOutcome;
use Boyd\LoginLibrary\Security\SessionManager;
use Exception;

/**
 * Self-service passwordless login: anyone who requests a code gets one, and a
 * first-time verified address is auto-registered -- with the single, fixed,
 * lowest-privilege role its PasswordlessUserRepository carries (there is no
 * roles table) -- instead of requiring an admin to have pre-added them first.
 * Reach for this class when requiring an admin to manually add every user
 * before any of them can sign in is the actual blocker -- e.g. a large,
 * informally-known population like referees at a sports league -- and the
 * auto-assigned role carries no capability an unvetted signup shouldn't have.
 *
 * This is the only path in the library to
 * AuthManager::establishPasswordlessSession(): a verified code and nothing
 * else. It is intentionally not usable as an alternate login for an account
 * that also has a password -- an earlier OtpController did that and was
 * removed, since letting either credential suffice weakens an account to
 * whichever is weaker, the opposite of a second factor. Something needing
 * stronger guarantees (elevated roles, admin access) should use password
 * login, or eventually a genuine password+OTP second factor (see the 2FA/MFA
 * TODO item) -- not this class against a pre-registered account.
 */
class SelfServiceOtpController
{
    public function __construct(
        private OtpManager $otpManager,
        private AuthManager $authManager,
        private PasswordlessUserRepository $userRepository,
        private SessionManager $sessionManager
    ) {}

    /**
     * Step 1. Always attempts to send a code for a syntactically valid
     * email -- no "is this address already registered" gate; a first-time
     * address is registered on a verified code, not on request.
     *
     * @return array{error: ?string, notice: ?string, advance: bool, email: string, csrfToken: string}
     */
    public function handleCodeRequest(
        string $method,
        ?string $csrfToken,
        ?string $email,
        string $ipAddress = '0.0.0.0'
    ): array {
        $error = null;
        $notice = null;
        $advance = false;
        $email = trim($email ?? '');

        if ($method === 'POST') {
            if (!$this->sessionManager->validateCsrfToken($csrfToken ?? '')) {
                $error = 'Your session expired or the form was invalid. Please try again.';
            } elseif ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $error = 'Please enter a valid email address.';
            } else {
                try {
                    $this->otpManager->startChallenge($email, $ipAddress);
                } catch (\Throwable $e) {
                    error_log('SelfServiceOtpController: startChallenge failed: ' . $e->getMessage());
                }
                $notice = 'A one-time code is on its way to that address. It expires shortly.';
                $advance = true;
            }
        }

        return [
            'error' => $error,
            'notice' => $notice,
            'advance' => $advance,
            'email' => $email,
            'csrfToken' => $this->sessionManager->generateCsrfToken(),
        ];
    }

    /**
     * Step 2. On a verified code, registers the address the first time it
     * ever signs in, then starts the session. A failure while doing so is
     * logged via error_log() for an operator to find, and the caller gets
     * back a generic AuthResult failure rather than a raw error.
     */
    public function handleCodeVerification(
        string $method,
        ?string $csrfToken,
        ?string $email,
        ?string $code,
        ?string $requestedRedirect,
        string $defaultRedirectUrl,
        string $ipAddress = '0.0.0.0'
    ): AuthResult {
        $redirectUrl = $defaultRedirectUrl;
        if (!empty($requestedRedirect) && str_starts_with($requestedRedirect, '/')) {
            $redirectUrl = $requestedRedirect;
        }

        if ($this->authManager->isLoggedIn()) {
            return new AuthResult(true, null, $redirectUrl, null);
        }

        $error = null;

        if ($method === 'POST') {
            if (!$this->sessionManager->validateCsrfToken($csrfToken ?? '')) {
                $error = 'Your session expired or the form was invalid. Please try again.';
            } else {
                $email = trim($email ?? '');
                $code = trim($code ?? '');

                if ($email === '' || $code === '') {
                    $error = 'Please enter the code we emailed you.';
                } else {
                    $outcome = $this->otpManager->verifyChallenge($email, $code, $ipAddress);

                    if ($outcome->isLockedOut()) {
                        $error = 'Too many incorrect attempts. Request a new code.';
                    } elseif (!$outcome->isVerified()) {
                        // Same message for "wrong code" and "no such challenge",
                        // so an attacker can't distinguish the two.
                        $error = 'That code is invalid or has expired. Request a new one.';
                    } else {
                        try {
                            $user = $this->userRepository->findOrRegister($email);
                            $this->authManager->establishPasswordlessSession($user, $ipAddress);
                            return new AuthResult(true, null, $redirectUrl, null);
                        } catch (Exception $e) {
                            error_log('SelfServiceOtpController: ' . $e->getMessage());
                            $error = 'Something went wrong signing you in. Please try again.';
                        }
                    }
                }
            }
        }

        return new AuthResult(false, $error, null, $this->sessionManager->generateCsrfToken());
    }
}
