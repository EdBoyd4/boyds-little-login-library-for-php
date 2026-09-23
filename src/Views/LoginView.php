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

                <?php $this->renderFragment($csrfToken, $error); ?>
            </div>
        </body>
        </html>
        <?php
    }

    /**
     * Just the error message and the form -- no <html>/<head>/<body>, no page
     * title, no stylesheet link. For an app that wants to place the login form
     * inside its own page layout (own chrome, own CSS) instead of render()'s
     * full standalone page. The caller owns the surrounding <title>/h1/nav; this
     * only owns the content that depends on login state.
     */
    public function renderFragment(string $csrfToken, ?string $error = null): void
    {
        ?>
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
        <?php
    }
}
