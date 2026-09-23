<?php
declare(strict_types=1);

namespace Boyd\LoginLibrary\Tests\Security;

use Boyd\LoginLibrary\Config\LoginConfig;
use Boyd\LoginLibrary\Security\SessionManager;
use PHPUnit\Framework\TestCase;

final class SessionManagerTimeoutTest extends TestCase
{
    protected function setUp(): void
    {
        $_SESSION = [];
    }

    protected function tearDown(): void
    {
        $_SESSION = [];
    }

    private function config(array $overrides = []): LoginConfig
    {
        return new LoginConfig(...array_merge(['dbHost' => 'h', 'dbName' => 'd', 'dbUser' => 'u', 'dbPass' => 'p'], $overrides));
    }

    public function test_library_defaults_are_four_hours_for_password_and_thirty_minutes_for_code_login(): void
    {
        $config = $this->config();

        $this->assertSame(14400, $config->getSessionTimeoutSeconds());
        $this->assertSame(1800, $config->getOtpSessionTimeoutSeconds());
    }

    public function test_a_password_session_uses_the_configured_timeout(): void
    {
        $manager = new SessionManager($this->config());

        $this->assertSame(14400, $manager->currentSessionTimeoutSeconds());
    }

    public function test_a_code_login_session_keeps_the_timeout_stamped_at_login(): void
    {
        // The manager enforcing the timeout may belong to a different config
        // than the one that established the session (gss88 shares one PHP
        // session between two identity stores), so the stamp must win.
        $_SESSION['session_timeout_seconds'] = 2700;
        $manager = new SessionManager($this->config(['sessionTimeoutSeconds' => 14400]));

        $this->assertSame(2700, $manager->currentSessionTimeoutSeconds());
    }

    public function test_a_malformed_stamp_falls_back_to_the_configured_timeout(): void
    {
        $manager = new SessionManager($this->config());

        foreach ([0, -5, '2700', null, []] as $bad) {
            $_SESSION['session_timeout_seconds'] = $bad;
            $this->assertSame(14400, $manager->currentSessionTimeoutSeconds());
        }
    }
}
