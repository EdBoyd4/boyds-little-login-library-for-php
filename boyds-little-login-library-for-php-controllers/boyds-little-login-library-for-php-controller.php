<?php
    declare(strict_types=1);

    function process_login_attempt(string $pageTitle, string $detailedTitle, string $csrfToken): void
    {
        // CSRF: your Auth::validateCsrfToken() dies() if invalid, so this is definitive.
        Auth::validateCsrfToken();

        $log_in_creds = verify_no_existing_user_credentials();
        if ($log_in_creds !== TRUE) {
            Auth::generateCsrfToken();
            display_login_page($pageTitle, $detailedTitle, $csrfToken, $log_in_creds);
            return;
        }

        $new_creds = verify_entry_of_username_and_password();
        if ($new_creds !== TRUE) {
            display_login_page($pageTitle, $detailedTitle, $csrfToken, $new_creds);
            return;
        }

        $user_name = trim((string)($_POST['username'] ?? ''));

        $user_password = trim((string)($_POST[PW_] ?? ''));
        
        $user_info_from_db = selectByUserName($user_name);

        $user_password_correct = validate_user_password($user_info_from_db, $user_password);
        if ($user_password_correct !== TRUE) {
            Auth::generateCsrfToken();
            display_login_page($pageTitle, $detailedTitle, $csrfToken, $user_password_correct);
            return;
        }

        $user_role_correct = validate_user_role($user_info_from_db);
        if ($user_role_correct !== TRUE) {
            Auth::generateCsrfToken();
            display_login_page($pageTitle, $detailedTitle, $csrfToken, $user_role_correct);
            return;
        }
        
        set_session_security_variables_and_associated_cookies($user_info_from_db);
        header('Location: /investigators.php');  // --- should be constant and concat? --------------------------------------------------------------
        exit;
        
        // if (validate_user_login($user_info_from_db, $user_password) === TRUE) {
        //     header('Location: /investigators.php');  // --- should be constant and concat? --------------------------------------------------------------
        //     exit;
        // }display_login_page($pageTitle, $detailedTitle, $csrfToken, 'Invalid login credentials.');
        
    }

    function process_login_page_request(string $pageTitle, string $detailedTitle, string $csrfToken): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            display_login_page($pageTitle, $detailedTitle, $csrfToken, null);
            return;
        }

        if (!Auth::validateCsrfToken()) {
            error_log('CSRF validation failure: ' . ($_SERVER['REMOTE_ADDR'] ?? 'unknown'));

            http_response_code(403);

            $csrfToken = Auth::generateCsrfToken();

            display_login_page($pageTitle, $detailedTitle, $csrfToken, 'Your session expired. Please try again.');

            return;
        }

        if (($_POST['login'] ?? '') !== 'login') {
            http_response_code(400);

            $csrfToken = Auth::generateCsrfToken();

            display_login_page($pageTitle, $detailedTitle, $csrfToken, 'Invalid login request.');

            return;
        }

        process_login_attempt($pageTitle, $detailedTitle, $csrfToken);
    }
