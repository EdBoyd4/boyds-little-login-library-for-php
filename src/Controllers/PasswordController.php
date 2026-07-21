<?php
declare(strict_types=1);

namespace Boyd\LoginLibrary\Controllers;

use Boyd\LoginLibrary\Repositories\UserRepository;
use Boyd\LoginLibrary\Repositories\SecurityAuditRepository;
use Boyd\LoginLibrary\Security\SessionManager;
use Exception;

class PasswordController
{
    public function __construct(
        private UserRepository $userRepository,
        private SessionManager $sessionManager,
        private ?SecurityAuditRepository $auditRepository = null
    ) {}

    /**
     * @return array{error: ?string, success: ?string, csrfToken: string}
     */
    public function handleChangePasswordRequest(
        string $method,
        ?string $csrfToken,
        ?string $currentPassword,
        ?string $newPassword,
        ?string $confirmPassword,
        string $ipAddress = '0.0.0.0'
    ): array {
        $error = null;
        $success = null;

        if ($method === 'POST') {
            if (!$this->sessionManager->validateCsrfToken($csrfToken ?? '')) {
                $error = 'Your session expired or form submission was invalid. Please try again.';
            } else {
                $currentPass = trim($currentPassword ?? '');
                $newPass = trim($newPassword ?? '');
                $confirmPass = trim($confirmPassword ?? '');
                
                $this->sessionManager->startSession();
                $username = $_SESSION['user_name'] ?? null;
                $userId = $_SESSION['user_id'] ?? null;

                if (empty($currentPass) || empty($newPass) || empty($confirmPass)) {
                    $error = 'Please complete all required fields.';
                } elseif ($newPass !== $confirmPass) {
                    $error = 'New password and confirmation do not match.';
                } elseif (!$username || !$userId) {
                     $error = 'User session not found.';
                } else {
                    try {
                        // Verify current password
                        $user = $this->userRepository->findByUsername($username);
                        if (!$user || !password_verify($currentPass, $user->getPasswordHash())) {
                            $error = 'Incorrect current password.';
                        } else {
                            $this->userRepository->updatePassword((int)$userId, $newPass);
                            $success = 'Password updated successfully.';

                            if ($this->auditRepository !== null) {
                                $this->auditRepository->logEvent(
                                    'password_changed',
                                    $ipAddress,
                                    $username,
                                    'User changed their own password.'
                                );
                            }
                        }
                    } catch (Exception $e) {
                        $error = $e->getMessage();
                    }
                }
            }
        }

        return [
            'error' => $error,
            'success' => $success,
            'csrfToken' => $this->sessionManager->generateCsrfToken()
        ];
    }
}
