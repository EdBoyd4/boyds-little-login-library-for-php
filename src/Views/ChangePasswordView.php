<?php
declare(strict_types=1);

namespace Boyd\LoginLibrary\Views;

class ChangePasswordView
{
    public function render(string $pageTitle, string $csrfToken, ?string $error = null, ?string $success = null, string $returnUrl = 'example_secure_page.php'): void
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

                <?php $this->renderFragment($csrfToken, $error, $success, $returnUrl); ?>
            </div>
        </body>
        </html>
        <?php
    }

    /**
     * Just the messages, form, and "return" link -- no <html>/<head>/<body>, no
     * page title, no stylesheet link. For embedding inside an app's own layout
     * instead of render()'s full standalone page.
     */
    public function renderFragment(string $csrfToken, ?string $error = null, ?string $success = null, string $returnUrl = 'example_secure_page.php'): void
    {
        ?>
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
            <a href="<?= htmlspecialchars($returnUrl, ENT_QUOTES, 'UTF-8') ?>">Return to Dashboard</a>
        </div>
        <?php
    }
}
