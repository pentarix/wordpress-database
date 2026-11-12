<?php

/**
 * @author    Sina Kuhestani <sinakuhestani@gmail.com>
 * @copyright 2026 Sina Kuhestani (EphpicMan)
 * @link      https://github.com/ephpicman GitHub
 * @link      https://www.linkedin.com/in/sinakuhestani LinkedIn
 * @license   See the LICENSE file in the project root.
 */

declare(strict_types=1);

namespace Pentarix\\WordPressDatabase\\WordPress;

use LogicException;

/**
 * A small wrapper around the native WordPress Transients API.
 */
final class Transient
{
    public static function get(string $name): mixed
    {
        self::ensureWordPressIsLoaded();

        return get_transient($name);
    }

    public static function set(string $name, mixed $value, int $expiration = 0): bool
    {
        self::ensureWordPressIsLoaded();

        return set_transient($name, $value, $expiration);
    }

    public static function delete(string $name): bool
    {
        self::ensureWordPressIsLoaded();

        return delete_transient($name);
    }

    public static function getSite(string $name): mixed
    {
        self::ensureWordPressIsLoaded();

        return get_site_transient($name);
    }

    public static function setSite(string $name, mixed $value, int $expiration = 0): bool
    {
        self::ensureWordPressIsLoaded();

        return set_site_transient($name, $value, $expiration);
    }

    public static function deleteSite(string $name): bool
    {
        self::ensureWordPressIsLoaded();

        return delete_site_transient($name);
    }

    private static function ensureWordPressIsLoaded(): void
    {
        if (!function_exists('get_transient')) {
            throw new LogicException('Transient requires WordPress to be loaded.');
        }
    }
}
