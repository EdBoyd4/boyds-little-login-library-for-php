<?php
declare(strict_types=1);

namespace Boyd\LoginLibrary\Views;

class RemoveUserView
{
    /**
     * @param array<int, array{id: int, username: string}> $users
     */
    public function render(string $pageTitle, string $csrfToken, array $users, ?string $error = null, ?string $success = null, string $returnUrl = 'example_secure_page.php'): void
    {
        ?>
        <!doctype html>
        <html lang="en">
        <head>
            <meta charset="utf-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title><?= htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8') ?></title>
            <link rel="stylesheet" href="login.css">
        </head>
        <body>
            <div class="container">
                <h1><?= htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8') ?></h1>
                <hr />

                <?php $this->renderFragment($csrfToken, $users, $error, $success, $returnUrl); ?>
            </div>
        </body>
        </html>
        <?php
    }

    /**
     * Just the messages, form, and "return" link -- no <html>/<head>/<body>, no
     * page title, no stylesheet link. For embedding inside an app's own layout
     * instead of render()'s full standalone page.
     *
     * @param array<int, array{id: int, username: string}> $users
     */
    public function renderFragment(string $csrfToken, array $users, ?string $error = null, ?string $success = null, string $returnUrl = 'example_secure_page.php'): void
    {
        ?>
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

            <button type="submit" class="danger">Remove User</button>
        </form>

        <div class="nav">
            <a href="<?= htmlspecialchars($returnUrl, ENT_QUOTES, 'UTF-8') ?>">Return to Dashboard</a>
        </div>
        <?php
    }
}
