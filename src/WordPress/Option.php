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
 * A small wrapper around the native WordPress Options API.
 */
final class Option
{
    public static function get(string $name, mixed $default = false): mixed
    {
        self::ensureWordPressIsLoaded();

        return get_option($name, $default);
    }

    public static function add(string $name, mixed $value, bool|string $autoload = true): bool
    {
        self::ensureWordPressIsLoaded();

        return add_option($name, $value, '', $autoload);
    }

    public static function update(string $name, mixed $value, bool|string|null $autoload = null): bool
    {
        self::ensureWordPressIsLoaded();

        return update_option($name, $value, $autoload);
    }

    public static function delete(string $name): bool
    {
        self::ensureWordPressIsLoaded();

        return delete_option($name);
    }

    /**
     * Check whether an option exists, including options whose value is false.
     */
    public static function exists(string $name): bool
    {
        self::ensureWordPressIsLoaded();

        $default = new \\stdClass();

        return get_option($name, $default) !== $default;
    }

    private static function ensureWordPressIsLoaded(): void
    {
        if (!function_exists('get_option')) {
            throw new LogicException('Option requires WordPress to be loaded.');
        }
    }
}
