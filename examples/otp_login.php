<?php
declare(strict_types=1);

// Passwordless login example: enter an email, receive a one-time code, enter it.
// This demonstrates the self-service pattern -- a first-time verified address is
// auto-registered under a fixed, low-privilege role, with NO password ever
// involved for these accounts. There is deliberately no example wiring OTP as an
// *alternate* login for an existing password-protected account: letting either
// credential suffice on its own weakens the account to whichever is weaker (see
// docs/boyds-little-login-library-for-php-TODO.md's 2FA item for the right way
// to combine the two -- password AND code, not password OR code).
//
// The library does not send mail, so this example ships a throwaway mailer that
// just writes the code to the PHP error log. Replace DemoOtpMailer with a real
// implementation of Boyd\LoginLibrary\Contracts\OtpMailerInterface in your app.
//
// init_login.php loads .env, builds LoginConfig, and wires $database,
// $userRepository, $attemptRepository, $auditRepository, $sessionManager,
// and $authManager -- see that file for what each is. This example reuses
// $database/$config/$sessionManager from it, but builds its OWN AuthManager
// with no password store (mirroring how a real app runs the self-service store
// as an identity pool separate from any password-protected one -- see gss88's
// rapp-bootstrap.php for a two-database version of this same split).
require_once __DIR__ . '/init_login.php';

use Boyd\LoginLibrary\Contracts\OtpMailerInterface;
use Boyd\LoginLibrary\Controllers\SelfServiceOtpController;
use Boyd\LoginLibrary\Repositories\OtpRepository;
use Boyd\LoginLibrary\Repositories\PasswordlessUserRepository;
use Boyd\LoginLibrary\Security\AuthManager;
use Boyd\LoginLibrary\Security\IpAttemptThrottle;
use Boyd\LoginLibrary\Security\OtpManager;
use Boyd\LoginLibrary\Views\OtpLoginView;

final class DemoOtpMailer implements OtpMailerInterface
{
    public function sendOtp(string $toEmail, string $code, int $ttlSeconds): void
    {
        error_log(sprintf('[DemoOtpMailer] code for %s is %s (valid %d min)', $toEmail, $code, intdiv($ttlSeconds, 60)));
    }
}

// Same IP-lockout table password login uses ($attemptRepository, from
// init_login.php) -- an IP can't dodge the per-address limits below by
// spreading OTP requests across many different email addresses.
$otpManager = OtpManager::fromConfig(
    repository: new OtpRepository($database, $config->getTableOtpChallenges()),
    mailer: new DemoOtpMailer(),
    config: $config,
    auditLogger: $auditRepository,
    ipThrottle: IpAttemptThrottle::fromConfig($attemptRepository, $config)
);

// No password, no roles table -- every user this returns carries the fixed
// role 'member'. Reuses the example's single database/table for simplicity;
// a real app typically keeps this in a database separate from any
// password-protected store, so the two pools can never collide.
$passwordlessUserRepository = new PasswordlessUserRepository($database, $config, 'member');
$passwordlessAuthManager = new AuthManager(
    userRepository: null, // can only establish a session, never check a password
    sessionManager: $sessionManager,
    config: $config,
);

$controller = new SelfServiceOtpController($otpManager, $passwordlessAuthManager, $passwordlessUserRepository, $sessionManager);
$view = new OtpLoginView();

$method = $_SERVER['REQUEST_METHOD'];
$ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
$step = $_POST['step'] ?? 'request';

if ($method === 'POST' && $step === 'verify') {
    $result = $controller->handleCodeVerification(
        method: $method,
        csrfToken: $_POST['csrf_token'] ?? null,
        email: $_POST['email'] ?? null,
        code: $_POST['code'] ?? null,
        requestedRedirect: $_GET['redirect'] ?? null,
        defaultRedirectUrl: '/example_secure_page.php',
        ipAddress: $ip
    );

    if ($result->success) {
        header('Location: ' . $result->redirectUrl);
        exit;
    }

    $view->renderVerifyForm(
        pageTitle: 'Your Organization, Inc.',
        detailedTitle: 'Enter your one-time code',
        csrfToken: $result->csrfToken,
        email: (string)($_POST['email'] ?? ''),
        error: $result->error
    );
    exit;
}

// Default path: step 1 (ask for email). A POST here also covers "send a new code".
$state = $controller->handleCodeRequest(
    method: $method,
    csrfToken: $_POST['csrf_token'] ?? null,
    email: $_POST['email'] ?? null,
    ipAddress: $ip
);

if ($state['advance']) {
    $view->renderVerifyForm(
        pageTitle: 'Your Organization, Inc.',
        detailedTitle: 'Enter your one-time code',
        csrfToken: $state['csrfToken'],
        email: $state['email'],
        notice: $state['notice']
    );
} else {
    $view->renderRequestForm(
        pageTitle: 'Your Organization, Inc.',
        detailedTitle: 'Sign in with a one-time code',
        csrfToken: $state['csrfToken'],
        error: $state['error'],
        prefillEmail: $state['email']
    );
}
