<?php
declare(strict_types=1);

namespace Boyd\LoginLibrary\Views;

class SessionSettingsView
{
    /**
     * @param int $currentSeconds Currently effective session length.
     * @param int $min            Lowest selectable value (seconds).
     * @param int $max            Highest selectable value (seconds).
     */
    public function render(
        string $pageTitle,
        string $csrfToken,
        int $currentSeconds,
        int $min,
        int $max,
        ?string $error = null,
        ?string $success = null,
        string $returnUrl = 'example_secure_page.php'
    ): void {
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

                <?php $this->renderFragment($csrfToken, $currentSeconds, $min, $max, $error, $success, $returnUrl); ?>
            </div>
        </body>
        </html>
        <?php
    }

    /**
     * Just the messages, explanatory text, and form -- no <html>/<head>/<body>,
     * no page title, no stylesheet link. For embedding inside an app's own
     * layout instead of render()'s full standalone page.
     *
     * @param int $currentSeconds Currently effective session length.
     * @param int $min            Lowest selectable value (seconds).
     * @param int $max            Highest selectable value (seconds).
     */
    public function renderFragment(
        string $csrfToken,
        int $currentSeconds,
        int $min,
        int $max,
        ?string $error = null,
        ?string $success = null,
        string $returnUrl = 'example_secure_page.php'
    ): void {
        $options = $this->buildOptions($currentSeconds, $min, $max);
        ?>
        <?php if ($error): ?>
            <div class="error"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
        <?php endif; ?>
        <?php if ($success): ?>
            <div class="success"><?= htmlspecialchars($success, ENT_QUOTES, 'UTF-8') ?></div>
        <?php endif; ?>

        <p>Signed-in users are automatically logged out after this much idle time.
           The current setting is <strong><?= htmlspecialchars($this->humanize($currentSeconds), ENT_QUOTES, 'UTF-8') ?></strong>.</p>

        <form method="post" action="">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">

            <label for="preset">Session length</label>
            <select name="preset" id="preset" onchange="document.getElementById('seconds').value = this.value;">
                <?php foreach ($options as $seconds => $label): ?>
                    <option value="<?= (int)$seconds ?>" <?= $seconds === $currentSeconds ? 'selected' : '' ?>>
                        <?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <label for="seconds">…or an exact number of seconds</label>
            <input type="number" name="seconds" id="seconds"
                   min="<?= (int)$min ?>" max="<?= (int)$max ?>" step="1"
                   value="<?= (int)$currentSeconds ?>" required>
            <p class="hint">Allowed range: <?= (int)$min ?>–<?= (int)$max ?> seconds
               (<?= htmlspecialchars($this->humanize($min), ENT_QUOTES, 'UTF-8') ?>
               – <?= htmlspecialchars($this->humanize($max), ENT_QUOTES, 'UTF-8') ?>).</p>

            <button type="submit">Save session length</button>
        </form>

        <div class="nav"><a href="<?= htmlspecialchars($returnUrl, ENT_QUOTES, 'UTF-8') ?>">Return to Dashboard</a></div>
        <?php
    }

    /**
     * Common presets, filtered to the configured range, with the current value
     * added in case it isn't one of them.
     *
     * @return array<int, string>
     */
    private function buildOptions(int $currentSeconds, int $min, int $max): array
    {
        $presets = [
            900 => '15 minutes',
            1800 => '30 minutes',
            3600 => '1 hour',
            14400 => '4 hours',
            28800 => '8 hours',
            43200 => '12 hours',
            86400 => '24 hours',
        ];
        $options = [];
        foreach ($presets as $seconds => $label) {
            if ($seconds >= $min && $seconds <= $max) {
                $options[$seconds] = $label;
            }
        }
        if (!isset($options[$currentSeconds])) {
            $options[$currentSeconds] = $this->humanize($currentSeconds) . ' (current)';
        }
        ksort($options);
        return $options;
    }

    private function humanize(int $seconds): string
    {
        if ($seconds % 3600 === 0) {
            $h = intdiv($seconds, 3600);
            return $h . ' hour' . ($h === 1 ? '' : 's');
        }
        if ($seconds % 60 === 0) {
            $m = intdiv($seconds, 60);
            return $m . ' minute' . ($m === 1 ? '' : 's');
        }
        return $seconds . ' seconds';
    }
}
