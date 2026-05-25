<?php
declare(strict_types=1);

namespace Boyd\LoginLibrary\Views;

class ChangePasswordView
{
    public function render(string $pageTitle, string $csrfToken, ?string $error = null, ?string $success = null): void
    {
        ?>
        <!doctype html>
        <html lang="en">
        <head>
            <meta charset="utf-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title><?= htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8') ?></title>
            <style>
                body { font-family: sans-serif; background: #f4f4f9; padding: 20px; }
                .container { background: white; padding: 20px; max-width: 400px; margin: auto; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
                .error { color: #d32f2f; background: #fdecea; padding: 10px; margin-bottom: 15px; border-radius: 4px; }
                .success { color: #2e7d32; background: #edf7ed; padding: 10px; margin-bottom: 15px; border-radius: 4px; }
                label { display: block; margin-bottom: 5px; font-weight: bold; }
                input[type="password"] { width: 100%; padding: 8px; margin-bottom: 15px; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box; }
                button { width: 100%; padding: 10px; background: #1976d2; color: white; border: none; border-radius: 4px; cursor: pointer; font-size: 16px; }
                button:hover { background: #115293; }
                .nav { margin-top: 15px; text-align: center; }
                .nav a { color: #1976d2; text-decoration: none; }
            </style>
        </head>
        <body>
            <div class="container">
                <h1><?= htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8') ?></h1>
                <hr />

                <?php if ($error): ?>
                    <div class="error"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
                <?php endif; ?>
                
                <?php if ($success): ?>
                    <div class="success"><?= htmlspecialchars($success, ENT_QUOTES, 'UTF-8') ?></div>
                <?php endif; ?>

                <form method="post" action="">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
                    
                    <label for="current_password">Current Password:</label>
                    <input type="password" name="current_password" id="current_password" required autocomplete="current-password">
                    
                    <label for="new_password">New Password:</label>
                    <input type="password" name="new_password" id="new_password" required autocomplete="new-password">
                    
                    <label for="confirm_password">Confirm New Password:</label>
                    <input type="password" name="confirm_password" id="confirm_password" required autocomplete="new-password">
                    
                    <button type="submit">Change Password</button>
                </form>
                
                <div class="nav">
                    <a href="example_secure_page.php">Return to Dashboard</a>
                </div>
            </div>
        </body>
        </html>
        <?php
    }
}
