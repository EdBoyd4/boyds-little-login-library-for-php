<?php
// 1. Include the initialization script
require_once __DIR__ . '/init_login.php';

// 2. Enforce that the user must be logged in to see this page!
// If they are not logged in, they will be redirected to the login page automatically.
$authManager->enforceLogin();

// (Optional) Enforce that the user must have a specific role
// $authManager->requireRole('ADM');

// If the script reaches this point, the user is authenticated and active!
$username = $_SESSION['user_name'] ?? 'Unknown User';

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Secured Dashboard</title>
</head>
<body>
    <h1>Welcome, <?php echo htmlspecialchars($username); ?>!</h1>
    <p>This is a highly secured area of the application.</p>
    <a href="logout.php">Log Out</a>
</body>
</html>
