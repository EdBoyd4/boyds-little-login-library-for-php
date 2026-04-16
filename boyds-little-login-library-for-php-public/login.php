<?php
declare(strict_types=1);
require_once dirname(__DIR__). '/boyds-little-login-library-for-php_constants/boyds-little-login-library-for-php-nav-constants.php';

require_once BOYDS_LOGIN_LIBRARY_SECURITY . '/BoydsLittleLoginLibraryForPhpUserAuthorization.php';
Auth::enforceHttps();

require_once BOYDS_LOGIN_LIBRARY_VIEWS . '/boyds-little-login-library-for-php-view.php';
require_once BOYDS_LOGIN_LIBRARY_CONTROLLERS . '/boyds-little-login-library-for-php-controller.php';
require_once BOYDS_LOGIN_LIBRARY_SECURITY . '/boyds-little-login-library-for-php-security-methods.php';
require_once BOYDS_LOGIN_LIBRARY_MODELS . '/boyds-little-login-library-for-php-model.php';

$pageCaptionArray = array('Clarium Investigations User Portal', 'Investigator Log In');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['login'] ?? '';
    if ($action === 'login') {
        process_login_attempt($pageCaptionArray);
        exit;
    }
}

// GET
display_login_page($pageCaptionArray);
