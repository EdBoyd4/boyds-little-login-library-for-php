<?php
declare(strict_types=1);

namespace Boyd\LoginLibrary\Controllers;

use Boyd\LoginLibrary\Repositories\UserRepository;
use Boyd\LoginLibrary\Repositories\SecurityAuditRepository;
use Boyd\LoginLibrary\Security\SessionManager;
use Exception;

class UserController
{
    public function __construct(
        private UserRepository $userRepository,
        private SessionManager $sessionManager,
        private ?SecurityAuditRepository $auditRepository = null
    ) {}

    /**
     * @return array{error: ?string, success: ?string, csrfToken: string, roles: array}
     */
    public function handleAddUserRequest(): array
    {
        $error = null;
        $success = null;

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $csrfToken = $_POST['csrf_token'] ?? '';

            if (!$this->sessionManager->validateCsrfToken($csrfToken)) {
                http_response_code(403);
                $error = 'Your session expired or form submission was invalid. Please try again.';
            } else {
                $username = trim((string)($_POST['username'] ?? ''));
                $password = trim((string)($_POST['password'] ?? ''));
                $roles = $_POST['roles'] ?? [];
                
                if (!is_array($roles)) {
                    $roles = [];
                }

                $userRoles = $_SESSION['user_roles'] ?? [];

                if (empty($username) || empty($password)) {
                    $error = 'Please complete all required fields.';
                } elseif (!in_array('CQM', $userRoles, true)) {
                    $error = 'Unauthorized: You do not have permission to create users.';
                } else {
                    try {
                        $this->userRepository->addUser($username, $password, $roles);
                        $success = 'User created successfully.';

                        if ($this->auditRepository !== null) {
                            $ipAddress = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
                            
                            // Let's get the role names for logging instead of IDs
                            $allRoles = $this->userRepository->getAllRoles();
                            $assignedRoleNames = [];
                            foreach ($roles as $roleId) {
                                if (isset($allRoles[$roleId])) {
                                    $assignedRoleNames[] = $allRoles[$roleId];
                                }
                            }

                            // Make sure session is started to get the current username
                            $this->sessionManager->startSession();
                            
                            $this->auditRepository->logEvent(
                                'user_created',
                                $ipAddress,
                                $_SESSION['user_name'] ?? 'Unknown User',
                                sprintf('Created new user: %s with roles: %s', $username, implode(', ', $assignedRoleNames))
                            );
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
            'csrfToken' => $this->sessionManager->generateCsrfToken(),
            'roles' => $this->userRepository->getAllRoles()
        ];
    }
}
