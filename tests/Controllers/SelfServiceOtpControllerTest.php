<?php
declare(strict_types=1);

namespace Boyd\LoginLibrary\Tests\Controllers;

use Boyd\LoginLibrary\Controllers\SelfServiceOtpController;
use Boyd\LoginLibrary\Models\AuthResult;
use Boyd\LoginLibrary\Models\User;
use Boyd\LoginLibrary\Repositories\PasswordlessUserRepository;
use Boyd\LoginLibrary\Security\AuthManager;
use Boyd\LoginLibrary\Security\OtpManager;
use Boyd\LoginLibrary\Security\SessionManager;
use Boyd\LoginLibrary\Tests\Support\InMemoryOtpRepository;
use Boyd\LoginLibrary\Tests\Support\RecordingAuditLogger;
use Boyd\LoginLibrary\Tests\Support\SpyOtpMailer;
use PHPUnit\Framework\TestCase;
use RuntimeException;

/**
 * PasswordlessUserRepository/AuthManager/SessionManager are concrete classes,
 * not interfaces -- PHPUnit's createMock() skips their real constructors (no
 * Database/LoginConfig needed), so these are pure mocks. OtpManager is the
 * real class wired to the same in-memory fakes OtpManagerTest uses, since
 * exercising the real issue/verify mechanics is the point.
 */
final class SelfServiceOtpControllerTest extends TestCase
{
    private const HMAC_KEY = 'test-only-hmac-key-0123456789abcdef';

    private InMemoryOtpRepository $otpRepo;
    private SpyOtpMailer $mailer;
    private OtpManager $otpManager;

    /** @var AuthManager&\PHPUnit\Framework\MockObject\MockObject */
    private AuthManager $authManager;

    /** @var PasswordlessUserRepository&\PHPUnit\Framework\MockObject\MockObject */
    private PasswordlessUserRepository $userRepository;

    /** @var SessionManager&\PHPUnit\Framework\MockObject\MockObject */
    private SessionManager $sessionManager;

    protected function setUp(): void
    {
        $this->otpRepo = new InMemoryOtpRepository();
        $this->mailer = new SpyOtpMailer();
        $this->otpManager = new OtpManager($this->otpRepo, $this->mailer, self::HMAC_KEY, new RecordingAuditLogger());

        $this->authManager = $this->createMock(AuthManager::class);
        $this->userRepository = $this->createMock(PasswordlessUserRepository::class);
        $this->sessionManager = $this->createMock(SessionManager::class);

        // Every test's CSRF check passes unless a test overrides it -- the
        // point of these tests is the self-service logic, not CSRF plumbing
        // (that's covered by test_invalid_csrf_token_rejects_the_verification_attempt below).
        $this->sessionManager->method('validateCsrfToken')->willReturn(true);
        $this->sessionManager->method('generateCsrfToken')->willReturn('csrf-token');
    }

    private function controller(): SelfServiceOtpController
    {
        return new SelfServiceOtpController(
            $this->otpManager,
            $this->authManager,
            $this->userRepository,
            $this->sessionManager
        );
    }

    /** Requests a code for $email and returns the plaintext the mailer received. */
    private function requestCode(SelfServiceOtpController $controller, string $email): string
    {
        $controller->handleCodeRequest('POST', 'csrf-token', $email, '127.0.0.1');
        return $this->mailer->lastCode();
    }

    public function test_code_request_sends_a_code_for_an_unregistered_address(): void
    {
        $state = $this->controller()->handleCodeRequest('POST', 'csrf-token', 'new@example.com', '127.0.0.1');

        $this->assertTrue($state['advance']);
        $this->assertNull($state['error']);
        $this->assertSame(1, $this->mailer->count());
        $this->assertSame('new@example.com', $this->mailer->sent[0]['email']);
    }

    public function test_code_request_rejects_a_malformed_address_without_sending_anything(): void
    {
        $state = $this->controller()->handleCodeRequest('POST', 'csrf-token', 'not-an-email', '127.0.0.1');

        $this->assertFalse($state['advance']);
        $this->assertNotNull($state['error']);
        $this->assertSame(0, $this->mailer->count());
    }

    public function test_verified_address_is_registered_and_signed_in(): void
    {
        $user = new User(id: 42, username: 'new@example.com', passwordHash: '', roles: ['ref']);
        $this->userRepository->expects($this->once())
            ->method('findOrRegister')
            ->with('new@example.com')
            ->willReturn($user);

        $this->authManager->method('isLoggedIn')->willReturn(false);
        $this->authManager->expects($this->once())
            ->method('establishPasswordlessSession')
            ->with($user, '127.0.0.1');

        $controller = $this->controller();
        $code = $this->requestCode($controller, 'new@example.com');

        $result = $controller->handleCodeVerification(
            'POST', 'csrf-token', 'new@example.com', $code, null, '/rapp/reports.php', '127.0.0.1'
        );

        $this->assertInstanceOf(AuthResult::class, $result);
        $this->assertTrue($result->success);
        $this->assertSame('/rapp/reports.php', $result->redirectUrl);
    }

    public function test_requested_redirect_is_honoured_only_when_it_is_a_local_path(): void
    {
        $this->userRepository->method('findOrRegister')
            ->willReturn(new User(id: 7, username: 'ref@example.com', passwordHash: '', roles: ['ref']));
        $this->authManager->method('isLoggedIn')->willReturn(false);

        $controller = $this->controller();
        $code = $this->requestCode($controller, 'ref@example.com');
        $local = $controller->handleCodeVerification(
            'POST', 'csrf-token', 'ref@example.com', $code, '/rapp/report.php', '/rapp/reports.php', '127.0.0.1'
        );

        $code = $this->requestCode($controller, 'ref@example.com');
        $offSite = $controller->handleCodeVerification(
            'POST', 'csrf-token', 'ref@example.com', $code, 'https://evil.example/', '/rapp/reports.php', '127.0.0.1'
        );

        $this->assertSame('/rapp/report.php', $local->redirectUrl);
        $this->assertSame('/rapp/reports.php', $offSite->redirectUrl);
    }

    public function test_wrong_code_does_not_establish_a_session(): void
    {
        $this->userRepository->expects($this->never())->method('findOrRegister');
        $this->authManager->method('isLoggedIn')->willReturn(false);
        $this->authManager->expects($this->never())->method('establishPasswordlessSession');

        $controller = $this->controller();
        $this->requestCode($controller, 'new@example.com');

        $result = $controller->handleCodeVerification(
            'POST', 'csrf-token', 'new@example.com', '000000', null, '/rapp/reports.php', '127.0.0.1'
        );

        $this->assertFalse($result->success);
        $this->assertNotNull($result->error);
    }

    public function test_registration_failure_fails_closed_without_leaking_the_cause(): void
    {
        $this->userRepository->method('findOrRegister')
            ->willThrowException(new RuntimeException('SQLSTATE[42S02]: Base table or view not found'));

        $this->authManager->method('isLoggedIn')->willReturn(false);
        $this->authManager->expects($this->never())->method('establishPasswordlessSession');

        $controller = $this->controller();
        $code = $this->requestCode($controller, 'new@example.com');

        $result = $controller->handleCodeVerification(
            'POST', 'csrf-token', 'new@example.com', $code, null, '/rapp/reports.php', '127.0.0.1'
        );

        $this->assertFalse($result->success);
        $this->assertNotNull($result->error);
        $this->assertStringNotContainsString('SQLSTATE', (string) $result->error);
    }

    public function test_already_logged_in_short_circuits_to_success_without_touching_otp_or_user_lookup(): void
    {
        $this->authManager->method('isLoggedIn')->willReturn(true);
        $this->userRepository->expects($this->never())->method('findOrRegister');

        $result = $this->controller()->handleCodeVerification(
            'GET', null, null, null, '/rapp/report.php', '/rapp/reports.php', '127.0.0.1'
        );

        $this->assertTrue($result->success);
        $this->assertSame('/rapp/report.php', $result->redirectUrl);
    }

    public function test_invalid_csrf_token_rejects_the_verification_attempt(): void
    {
        $this->sessionManager = $this->createMock(SessionManager::class);
        $this->sessionManager->method('validateCsrfToken')->willReturn(false);
        $this->sessionManager->method('generateCsrfToken')->willReturn('csrf-token');
        $this->authManager->method('isLoggedIn')->willReturn(false);
        $this->userRepository->expects($this->never())->method('findOrRegister');

        $result = $this->controller()->handleCodeVerification(
            'POST', 'wrong-token', 'new@example.com', '123456', null, '/rapp/reports.php', '127.0.0.1'
        );

        $this->assertFalse($result->success);
    }
}
