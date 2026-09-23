<?php
declare(strict_types=1);

namespace Boyd\LoginLibrary\Views;

class AddUserView
{
    public function render(string $pageTitle, string $csrfToken, array $roles, ?string $error = null, ?string $success = null, string $returnUrl = 'example_secure_page.php'): void
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

                <?php $this->renderFragment($csrfToken, $roles, $error, $success, $returnUrl); ?>
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
    public function renderFragment(string $csrfToken, array $roles, ?string $error = null, ?string $success = null, string $returnUrl = 'example_secure_page.php'): void
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
            <a href="<?= htmlspecialchars($returnUrl, ENT_QUOTES, 'UTF-8') ?>">Return to Dashboard</a>
        </div>
        <?php
    }
}
