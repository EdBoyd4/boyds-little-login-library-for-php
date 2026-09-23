<?php
declare(strict_types=1);

namespace Boyd\LoginLibrary\Controllers;

use Boyd\LoginLibrary\Config\LoginConfig;
use Boyd\LoginLibrary\Contracts\SecurityAuditLoggerInterface;
use Boyd\LoginLibrary\Repositories\SettingsRepository;
use Boyd\LoginLibrary\Security\SessionManager;

/**
 * Admin-only editing of runtime auth settings. Currently a single setting:
 * session_timeout_seconds, consumed by SessionManager::getEffectiveTimeoutSeconds().
 */
class SettingsController
{
    public function __construct(
        private SettingsRepository $settingsRepository,
        private SessionManager $sessionManager,
        private LoginConfig $config,
        private ?SecurityAuditLoggerInterface $auditRepository = null,
        private ?string $userManagerRole = null
    ) {}

    /**
     * @return array{
     *   error: ?string, success: ?string, csrfToken: string,
     *   currentSeconds: int, min: int, max: int
     * }
     */
    public function handleSessionTimeoutRequest(
        string $method,
        ?string $csrfToken,
        ?string $submittedSeconds,
        string $ipAddress = '0.0.0.0'
    ): array {
        $role = $this->userManagerRole ?? $this->config->getUserManagerRole();
        $min = $this->config->getSessionTimeoutSecondsMin();
        $max = $this->config->getSessionTimeoutSecondsMax();

        $error = null;
        $success = null;

        if ($method === 'POST') {
            $submitted = trim($submittedSeconds ?? '');

            if (!$this->sessionManager->validateCsrfToken($csrfToken ?? '')) {
                $error = 'Your session expired or form submission was invalid. Please try again.';
            } elseif (!in_array($role, $_SESSION['user_roles'] ?? [], true)) {
                $error = 'Unauthorized: You do not have permission to change session settings.';
            } elseif ($submitted === '' || !ctype_digit($submitted)) {
                $error = 'Please choose a valid session length.';
            } elseif ((int)$submitted < $min || (int)$submitted > $max) {
                $error = sprintf(
                    'Session length must be between %d and %d seconds (%d min – %d hr).',
                    $min, $max, intdiv($min, 60), intdiv($max, 3600)
                );
            } else {
                try {
                    $this->settingsRepository->set('session_timeout_seconds', (string)(int)$submitted);
                    $success = 'Session length updated. It takes effect on the next request.';
                    $this->auditRepository?->logEvent(
                        'session_timeout_changed',
                        $ipAddress,
                        $_SESSION['user_name'] ?? 'Unknown User',
                        sprintf('Session timeout set to %d seconds', (int)$submitted)
                    );
                } catch (\Throwable $e) {
                    error_log('SettingsController: could not save session_timeout_seconds: ' . $e->getMessage());
                    $error = 'Could not save the new session length. Check that the auth_settings table exists.';
                }
            }
        }

        $stored = $this->settingsRepository->get('session_timeout_seconds');
        $currentSeconds = ($stored !== null && ctype_digit($stored))
            ? max($min, min($max, (int)$stored))
            : $this->config->getSessionTimeoutSeconds();

        return [
            'error' => $error,
            'success' => $success,
            'csrfToken' => $this->sessionManager->generateCsrfToken(),
            'currentSeconds' => $currentSeconds,
            'min' => $min,
            'max' => $max,
        ];
    }
}
