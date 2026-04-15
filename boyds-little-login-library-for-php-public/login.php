<?php
declare(strict_types=1);
require_once dirname(__DIR__). '/boyds-little-login-library-for-php_constants/boyds-little-login-library-for-php_nav_constants.php';

require_once BOYDS_LOGIN_LIBRARY_SECURITY . '/auth.php';
Auth::enforceHttps();

require_once BOYDS_LOGIN_LIBRARY_VIEWS . '/boyds-little-login-library-for-php_view.php';
require_once BOYDS_LOGIN_LIBRARY_CONTROLLERS . '/boyds-little-login-library-for-php_controller.php';
require_once BOYDS_LOGIN_LIBRARY_SECURITY . '/boyds-little-login-library-for-php_security.php';
require_once BOYDS_LOGIN_LIBRARY_MODELS . '/boyds-little-login-library-for-php_model.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['login'] ?? '';
    if ($action === 'login') {
        process_login_attempt();
        exit;
    }
}

// GET
display_login_page('Clarium Investigations User Portal', 'Investigator Log In');
