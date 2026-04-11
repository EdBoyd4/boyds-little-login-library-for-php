<?php
declare(strict_types=1);

function process_login_attempt(): void
{
    // CSRF: your Auth::validateCsrfToken() dies() if invalid, so this is definitive.
    Auth::validateCsrfToken();

    $log_in_creds = verify_no_existing_user_credentials();
    if ($log_in_creds !== TRUE) {
        echo $log_in_creds;
        display_login_page();
        return;
    }

    $new_creds = verify_entry_of_username_and_password();
    if ($new_creds !== TRUE) {
        echo $new_creds;
        display_login_page();
        return;
    }

    $user_name = trim((string)($_POST['username'] ?? ''));

    // IMPORTANT: your security file checks PW_ but your controller used 'password'.
    // Align controller to PW_ (constant).
    $user_password = trim((string)($_POST[PW_] ?? ''));

    $user_info = validateUser($user_name);
    closeLoginDatabase();

    if (validate_user_login($user_info, $user_password) === TRUE) {
        header('Location: /investigators.php');  // --- should be constant and concat?
        exit;
    }

    echo "Invalid login credentials.";
    display_login_page();
}

/* function process_login_page_request(){
    
    if($_SERVER['REQUEST_METHOD'] == 'POST'){
        switch ($_POST['login']) {
            case "login":
                $csrfToken = Auth::validateCsrfToken();
                process_login_attempt();
            break;
        }
    }else{
        display_login_page();        
    }
}

process_login_page_request(); */