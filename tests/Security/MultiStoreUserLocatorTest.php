<?php
declare(strict_types=1);

namespace Boyd\LoginLibrary\Tests\Security;

use Boyd\LoginLibrary\Contracts\UserFinderInterface;
use Boyd\LoginLibrary\Models\User;
use Boyd\LoginLibrary\Repositories\UserRepository;
use Boyd\LoginLibrary\Security\MultiStoreUserLocator;
use PHPUnit\Framework\TestCase;

final class MultiStoreUserLocatorTest extends TestCase
{
    private function repoReturning(?User $user): UserRepository
    {
        /** @var UserRepository&\PHPUnit\Framework\MockObject\MockObject $repo */
        $repo = $this->createMock(UserRepository::class);
        $repo->method('findByUsername')->willReturn($user);
        return $repo;
    }

    public function test_locates_the_store_holding_the_username(): void
    {
        $user = new User(id: 1, username: 'rra@example.com', passwordHash: 'x', roles: ['rra']);
        $locator = new MultiStoreUserLocator([
            'user_access' => $this->repoReturning($user),
            'otp_users' => $this->repoReturning(null),
        ]);

        $this->assertSame('user_access', $locator->locate('rra@example.com'));
        $this->assertFalse($locator->isAvailableEverywhere('rra@example.com'));
    }

    public function test_returns_null_when_the_username_is_in_no_store(): void
    {
        $locator = new MultiStoreUserLocator([
            'user_access' => $this->repoReturning(null),
            'otp_users' => $this->repoReturning(null),
        ]);

        $this->assertNull($locator->locate('nobody@example.com'));
        $this->assertTrue($locator->isAvailableEverywhere('nobody@example.com'));
    }

    public function test_checks_stores_in_the_given_order_and_stops_at_the_first_match(): void
    {
        $user = new User(id: 2, username: 'ref@example.com', passwordHash: 'x', roles: ['ref']);

        /** @var UserRepository&\PHPUnit\Framework\MockObject\MockObject $second */
        $second = $this->createMock(UserRepository::class);
        $second->expects($this->never())->method('findByUsername');

        $locator = new MultiStoreUserLocator([
            'user_access' => $this->repoReturning($user),
            'otp_users' => $second,
        ]);

        $this->assertSame('user_access', $locator->locate('ref@example.com'));
    }
}
