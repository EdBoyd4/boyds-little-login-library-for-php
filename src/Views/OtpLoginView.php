<?php
declare(strict_types=1);

namespace Boyd\LoginLibrary\Views;

/**
 * Renders the two steps of passwordless login. Step 1 collects an email address;
 * step 2 collects the code that was emailed. Both post back to the same page.
 */
class OtpLoginView
{
    public function renderRequestForm(
        string $pageTitle,
        string $detailedTitle,
        string $csrfToken,
        ?string $error = null,
        ?string $prefillEmail = null,
        array $extraHiddenFields = []
    ): void {
        $this->page($pageTitle, $detailedTitle, function () use ($csrfToken, $error, $prefillEmail, $extraHiddenFields) {
            $this->renderRequestFormFragment($csrfToken, $error, $prefillEmail, $extraHiddenFields);
        });
    }

    public function renderVerifyForm(
        string $pageTitle,
        string $detailedTitle,
        string $csrfToken,
        string $email,
        ?string $error = null,
        ?string $notice = null,
        ?string $codeLabel = null,
        array $extraHiddenFields = []
    ): void {
        $this->page($pageTitle, $detailedTitle, function () use ($csrfToken, $email, $error, $notice, $codeLabel, $extraHiddenFields) {
            $this->renderVerifyFormFragment($csrfToken, $email, $error, $notice, $codeLabel, $extraHiddenFields);
        });
    }

    /**
     * Step 1 content only: the error message and the email form -- no
     * <html>/<head>/<body>, no page title, no stylesheet link. For embedding
     * inside an app's own layout instead of renderRequestForm()'s full
     * standalone page.
     *
     * @param array<string, string> $extraHiddenFields Extra name => value hidden
     *        fields to carry through the form untouched (e.g. a post-login
     *        redirect target the host page is tracking) -- the library has no
     *        opinion on what these are, it just round-trips them.
     */
    public function renderRequestFormFragment(
        string $csrfToken,
        ?string $error = null,
        ?string $prefillEmail = null,
        array $extraHiddenFields = []
    ): void {
        ?>
        <?php if ($error): ?><div class="error"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div><?php endif; ?>
        <form method="post" action="">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
            <input type="hidden" name="step" value="request">
            <?php $this->renderHiddenFields($extraHiddenFields); ?>

            <label for="email">Email address</label>
            <input type="email" name="email" id="email" required autocomplete="email"
                   value="<?= htmlspecialchars($prefillEmail ?? '', ENT_QUOTES, 'UTF-8') ?>">

            <button type="submit">Email me a code</button>
        </form>
        <?php
    }

    /**
     * Step 2 content only: the error/notice message and the code form -- no
     * <html>/<head>/<body>, no page title, no stylesheet link. For embedding
     * inside an app's own layout instead of renderVerifyForm()'s full
     * standalone page.
     *
     * @param ?string $codeLabel Override for the code field's <label> text,
     *        e.g. a plain "One-time code" for a host that already states the
     *        destination address in its own lead text above this fragment.
     *        Defaults to "Enter the code sent to {$email}".
     * @param array<string, string> $extraHiddenFields See renderRequestFormFragment().
     *        Carried through both forms below (the main verify and the resend).
     */
    public function renderVerifyFormFragment(
        string $csrfToken,
        string $email,
        ?string $error = null,
        ?string $notice = null,
        ?string $codeLabel = null,
        array $extraHiddenFields = []
    ): void {
        $codeLabel ??= 'Enter the code sent to ' . $email;
        ?>
        <?php if ($error): ?><div class="error"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div><?php endif; ?>
        <?php if ($notice): ?><div class="notice"><?= htmlspecialchars($notice, ENT_QUOTES, 'UTF-8') ?></div><?php endif; ?>
        <form method="post" action="">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
            <input type="hidden" name="step" value="verify">
            <input type="hidden" name="email" value="<?= htmlspecialchars($email, ENT_QUOTES, 'UTF-8') ?>">
            <?php $this->renderHiddenFields($extraHiddenFields); ?>

            <label for="code"><?= htmlspecialchars($codeLabel, ENT_QUOTES, 'UTF-8') ?></label>
            <input type="text" name="code" id="code" required
                   inputmode="numeric" autocomplete="one-time-code"
                   pattern="[0-9]*" maxlength="10" autofocus>

            <button type="submit">Sign in</button>
        </form>
        <form method="post" action="" class="secondary">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
            <input type="hidden" name="step" value="request">
            <input type="hidden" name="email" value="<?= htmlspecialchars($email, ENT_QUOTES, 'UTF-8') ?>">
            <?php $this->renderHiddenFields($extraHiddenFields); ?>
            <button type="submit" class="linkish">Send a new code</button>
        </form>
        <?php
    }

    /** @param array<string, string> $fields */
    private function renderHiddenFields(array $fields): void
    {
        foreach ($fields as $name => $value) {
            ?><input type="hidden" name="<?= htmlspecialchars((string) $name, ENT_QUOTES, 'UTF-8') ?>" value="<?= htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8') ?>"><?php
        }
    }

    private function page(string $pageTitle, string $detailedTitle, callable $body): void
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
                <?php if ($detailedTitle): ?><h2><?= htmlspecialchars($detailedTitle, ENT_QUOTES, 'UTF-8') ?></h2><?php endif; ?>
                <?php $body(); ?>
            </div>
        </body>
        </html>
        <?php
    }
}
