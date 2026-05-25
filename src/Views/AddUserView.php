<?php
declare(strict_types=1);

namespace Boyd\LoginLibrary\Views;

class AddUserView
{
    public function render(string $pageTitle, string $csrfToken, array $roles, ?string $error = null, ?string $success = null): void
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
                input[type="text"], input[type="password"] { width: 100%; padding: 8px; margin-bottom: 15px; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box; }
                fieldset { border: 1px solid #ccc; border-radius: 4px; padding: 10px; margin-bottom: 15px; }
                fieldset label { font-weight: normal; display: flex; align-items: center; }
                fieldset input { margin-right: 8px; }
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
                    
                    <label for="username">New Username:</label>
                    <input type="text" name="username" id="username" required autocomplete="off">
                    
                    <label for="password">New Password:</label>
                    <input type="password" name="password" id="password" required autocomplete="new-password">
                    
                    <fieldset>
                        <legend>Select Roles:</legend>
                        <?php foreach ($roles as $roleId => $roleName): ?>
                            <label>
                                <input type="checkbox" name="roles[]" value="<?= htmlspecialchars((string)$roleId, ENT_QUOTES, 'UTF-8') ?>">
                                <?= htmlspecialchars($roleName, ENT_QUOTES, 'UTF-8') ?>
                            </label>
                        <?php endforeach; ?>
                    </fieldset>
                    
                    <button type="submit">Create User</button>
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
