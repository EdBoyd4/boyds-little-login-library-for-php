<?php
declare(strict_types=1);

namespace Boyd\LoginLibrary\Tests\Security;

use Boyd\LoginLibrary\Config\LoginConfig;
use Boyd\LoginLibrary\Repositories\AttemptRepository;
use Boyd\LoginLibrary\Security\IpAttemptThrottle;
use PHPUnit\Framework\TestCase;

/**
 * AttemptRepository is a concrete class, not an interface -- createMock()
 * skips its real constructor (no Database needed), so these are pure mocks.
 */
final class IpAttemptThrottleTest extends TestCase
{
    /** @var AttemptRepository&\PHPUnit\Framework\MockObject\MockObject */
    private AttemptRepository $repository;

    protected function setUp(): void
    {
        $this->repository = $this->createMock(AttemptRepository::class);
    }

    public function test_not_locked_out_below_the_threshold(): void
    {
        $this->repository->method('countRecentAttempts')->with('1.2.3.4', 900)->willReturn(4);

        $throttle = new IpAttemptThrottle($this->repository, maxAttempts: 5, lockoutSeconds: 900);

        $this->assertFalse($throttle->isLockedOut('1.2.3.4'));
    }

    public function test_locked_out_at_the_threshold(): void
    {
        $this->repository->method('countRecentAttempts')->willReturn(5);

        $throttle = new IpAttemptThrottle($this->repository, maxAttempts: 5, lockoutSeconds: 900);

        $this->assertTrue($throttle->isLockedOut('1.2.3.4'));
    }

    public function test_record_failure_delegates_to_the_repository(): void
    {
        $this->repository->expects($this->once())
            ->method('recordAttempt')
            ->with('1.2.3.4', 'ref@example.com');

        (new IpAttemptThrottle($this->repository, 5, 900))->recordFailure('1.2.3.4', 'ref@example.com');
    }

    public function test_clear_delegates_to_the_repository(): void
    {
        $this->repository->expects($this->once())->method('clearAttempts')->with('1.2.3.4');

        (new IpAttemptThrottle($this->repository, 5, 900))->clear('1.2.3.4');
    }

    public function test_from_config_pulls_thresholds_from_login_config(): void
    {
        $this->repository->expects($this->once())
            ->method('countRecentAttempts')
            ->with('1.2.3.4', 1200)
            ->willReturn(3);

        $config = new LoginConfig(
            dbHost: 'localhost', dbName: 'x', dbUser: 'x', dbPass: 'x',
            maxLoginAttempts: 3, lockoutTimeMinutes: 20
        );

        $throttle = IpAttemptThrottle::fromConfig($this->repository, $config);

        $this->assertTrue($throttle->isLockedOut('1.2.3.4'));
    }
}
