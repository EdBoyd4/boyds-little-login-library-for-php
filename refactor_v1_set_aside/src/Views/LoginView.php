<?php
declare(strict_types=1);

namespace Boyd\LoginLibrary\Views;

class LoginView
{
    public function render(string $pageTitle, string $detailedTitle, string $csrfToken, ?string $error = null): void
    {
        // Simple HTML output
        // In a more complex app, this might use a template engine like Twig, 
        // or extract variables and `require` a .phtml template file.
        ?>
        <!doctype html>
        <html lang="en">
        <head>
            <meta charset="utf-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title><?= htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8') ?></title>
            <style>
                body {
                    font-family: Arial, Helvetica, sans-serif;
                    font-size: 14px;
                    background-color: #f4f4f9;
                    margin: 0;
                    padding: 0;
                    display: flex;
                    justify-content: center;
                    align-items: center;
                    height: 100vh;
                }
                .container {
                    background: #fff;
                    padding: 20px 30px;
                    border-radius: 8px;
                    box-shadow: 0 4px 6px rgba(0,0,0,0.1);
                    width: 100%;
                    max-width: 350px;
                }
                h1, h2 {
                    text-align: center;
                    color: #333;
                }
                hr {
                    border: 0;
                    height: 1px;
                    background: #ccc;
                    margin-bottom: 20px;
                }
                .error {
                    color: #d9534f;
                    background: #fdf7f7;
                    border: 1px solid #d9534f;
                    padding: 10px;
                    border-radius: 4px;
                    margin-bottom: 15px;
                }
                label {
                    display: block;
                    margin-bottom: 5px;
                    color: #555;
                }
                input[type="text"],
                input[type="password"] {
                    width: 100%;
                    padding: 10px;
                    margin-bottom: 15px;
                    border: 1px solid #ccc;
                    border-radius: 4px;
                    box-sizing: border-box; /* Ensures padding doesn't affect width */
                }
                button {
                    width: 100%;
                    padding: 10px;
                    background-color: #3333cc;
                    border: none;
                    color: white;
                    border-radius: 4px;
                    font-size: 16px;
                    cursor: pointer;
                }
                button:hover {
                    background-color: #2828a6;
                }
            </style>
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
