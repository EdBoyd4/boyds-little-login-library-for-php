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
    public function handleChangePasswordRequest(): array
    {
        $error = null;
        $success = null;

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $csrfToken = $_POST['csrf_token'] ?? '';

            if (!$this->sessionManager->validateCsrfToken($csrfToken)) {
                http_response_code(403);
                $error = 'Your session expired or form submission was invalid. Please try again.';
            } else {
                $currentPassword = trim((string)($_POST['current_password'] ?? ''));
                $newPassword = trim((string)($_POST['new_password'] ?? ''));
                $confirmPassword = trim((string)($_POST['confirm_password'] ?? ''));
                
                $this->sessionManager->startSession();
                $username = $_SESSION['user_name'] ?? null;
                $userId = $_SESSION['user_id'] ?? null;

                if (empty($currentPassword) || empty($newPassword) || empty($confirmPassword)) {
                    $error = 'Please complete all required fields.';
                } elseif ($newPassword !== $confirmPassword) {
                    $error = 'New password and confirmation do not match.';
                } elseif (!$username || !$userId) {
                     $error = 'User session not found.';
                } else {
                    try {
                        // Verify current password
                        $user = $this->userRepository->findByUsername($username);
                        if (!$user || !password_verify($currentPassword, $user->getPasswordHash())) {
                            $error = 'Incorrect current password.';
                        } else {
                            $this->userRepository->updatePassword((int)$userId, $newPassword);
                            $success = 'Password updated successfully.';

                            if ($this->auditRepository !== null) {
                                $ipAddress = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
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
