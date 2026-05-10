<?php
declare(strict_types=1);

namespace Boyd\LoginLibrary\Views;

class LoginView
{
    public function render(string $pageTitle, string $detailedTitle, string $csrfToken, ?string $error = null): void
    {
        // Simple HTML output
        // We've consolidated do_html_header, display_login_form, and do_html_footer into one cohesive template
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
                <?php if ($detailedTitle): ?>
                    <h2><?= htmlspecialchars($detailedTitle, ENT_QUOTES, 'UTF-8') ?></h2>
                <?php endif; ?>

                <?php if ($error): ?>
                    <div class="error"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
                <?php endif; ?>

                <form name="login" method="post" action="">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
                    
                    <label for="username">Username:</label>
                    <input type="text" name="username" id="username" required autocomplete="username">
                    
                    <label for="password">Password:</label>
                    <input type="password" name="password" id="password" required autocomplete="current-password">
                    
                    <button type="submit" value="login" name="login">Log In</button>
                </form>
            </div>
        </body>
        </html>
        <?php
    }
}
