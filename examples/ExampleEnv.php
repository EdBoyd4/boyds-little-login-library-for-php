<?php
declare(strict_types=1);

/**
 * Tiny dependency-free .env reader for the example bootstrap below. Returns
 * a key/value array; it does NOT touch $_ENV / $_SERVER / putenv, so loading
 * it can never silently affect anything else in the process.
 *
 * This library requires nothing but PHP itself (see composer.json) -- no
 * host application should need to pull in a third-party .env package just to
 * follow this example. Not vlucas/phpdotenv's default global-mutating
 * behavior, and deliberately not parse_ini_file() either: INI syntax treats
 * a bare ';' as a comment starter, so a password containing one would be
 * silently truncated. A flat KEY=VALUE line reader has no such footgun for
 * credential values.
 *
 * This exact ~30-line pattern is what both of this library's real-world
 * consumers (gss88 and Clarassign) settled on independently, after starting
 * from phpdotenv -- copy it into your own host application, or use whatever
 * config-loading approach you already have. LoginConfig's constructor takes
 * plain strings; it doesn't care where they came from.
 */
final class ExampleEnv
{
    /**
     * Reads the first readable .env file found in $paths (in order) and
     * returns it as an array. Throws if none of them exist/are readable --
     * login cannot function without DB credentials, so a missing .env should
     * fail loudly and immediately, not continue silently and surface later
     * as a confusing, unrelated-looking DB-connection error.
     *
     * @param list<string> $paths directories to check, each tried as "$dir/.env"
     * @return array<string, string>
     */
    public static function load(array $paths): array
    {
        foreach ($paths as $dir) {
            $path = rtrim($dir, '/') . '/.env';
            if (is_readable($path)) {
                return self::parse($path);
            }
        }
        throw new RuntimeException('No readable .env file found in: ' . implode(', ', $paths));
    }

    /** @return array<string, string> */
    private static function parse(string $path): array
    {
        $out = [];
        foreach (file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
            $line = trim($line);
            if ($line === '' || $line[0] === '#') {
                continue;
            }
            $eq = strpos($line, '=');
            if ($eq === false) {
                continue;
            }
            $key = trim(substr($line, 0, $eq));
            $val = trim(substr($line, $eq + 1));
            if (strlen($val) >= 2
                && ($val[0] === '"' || $val[0] === "'")
                && $val[strlen($val) - 1] === $val[0]) {
                $val = substr($val, 1, -1);
            }
            if ($key !== '') {
                $out[$key] = $val;
            }
        }
        return $out;
    }
}
