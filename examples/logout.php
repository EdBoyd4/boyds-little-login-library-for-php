<?php
declare(strict_types=1);

require_once __DIR__ . '/init_login.php';

// Assuming $authManager is available from init_login.php
if ($authManager->isLoggedIn()) {
    $authManager->logout();
}

// Redirect to the login page
header("Location: login.php");
exit;
