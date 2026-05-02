<?php
    declare(strict_types=1);
    session_start();
    require_once dirname(__DIR__). '/boyds-little-login-library-for-php-constants/boyds-little-login-library-for-php-nav-constants.php';
    require_once BOYDS_LOGIN_LIBRARY_CONSTANTS . '/boyds-little-login-library-for-php-db-constants.php';
    require_once BOYDS_LOGIN_LIBRARY_SECURITY . '/boyds-little-login-library-for-php-user-authorization-methods.php';
    Auth::enforceHttps();
    $csrfToken = Auth::generateCsrfToken();
    require_once BOYDS_LOGIN_LIBRARY_SECURITY . '/boyds-little-login-library-for-php-login-security-methods.php';

    require_once BOYDS_LOGIN_LIBRARY_VIEWS . '/boyds-little-login-library-for-php-view.php';
    require_once BOYDS_LOGIN_LIBRARY_CONTROLLERS . '/boyds-little-login-library-for-php-controller.php';
    require_once BOYDS_LOGIN_LIBRARY_MODELS . '/boyds-little-login-library-for-php-db-methods.php';

    process_login_page_request('Clarium Investigations User Portal', 'Investigator Log In', $csrfToken);