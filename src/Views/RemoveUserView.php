<?php
declare(strict_types=1);

namespace Boyd\LoginLibrary\Views;

class RemoveUserView
{
    /**
     * @param array<int, array{id: int, username: string}> $users
     */
    public function render(string $pageTitle, string $csrfToken, array $users, ?string $error = null, ?string $success = null): void
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
                select { width: 100%; padding: 8px; margin-bottom: 15px; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box; }
                button { width: 100%; padding: 10px; background: #d32f2f; color: white; border: none; border-radius: 4px; cursor: pointer; font-size: 16px; }
                button:hover { background: #b71c1c; }
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

                <form method="post" action="" onsubmit="return confirm('Are you sure you want to remove this user? This action cannot be undone.');">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
                    
                    <label for="user_id">Select User to Remove:</label>
                    <select name="user_id" id="user_id" required>
                        <option value="">-- Select a User --</option>
                        <?php foreach ($users as $user): ?>
                            <option value="<?= htmlspecialchars((string)$user['id'], ENT_QUOTES, 'UTF-8') ?>">
                                <?= htmlspecialchars($user['username'], ENT_QUOTES, 'UTF-8') ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    
                    <button type="submit">Remove User</button>
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
