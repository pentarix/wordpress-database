<?php

/**
 * @author    Sina Kuhestani <sinakuhestani@gmail.com>
 * @copyright 2026 Sina Kuhestani (EphpicMan)
 * @link      https://github.com/ephpicman GitHub
 * @link      https://www.linkedin.com/in/sinakuhestani LinkedIn
 * @license   See the LICENSE file in the project root.
 */

declare(strict_types=1);

namespace Pentarix\WordPressDatabase\WordPress;

use LogicException;

/**
 * A thin wrapper around the native WordPress User API.
 */
final class User
{
    /**
     * Create a user.
     *
     * @return int|\WP_Error The user ID or a WordPress error.
     */
    public static function create(array|object $userData): int|\WP_Error
    {
        self::ensureWordPressIsLoaded();

        return wp_insert_user($userData);
    }

    /**
     * Update an existing user.
     *
     * @return int|\WP_Error The user ID or a WordPress error.
     */
    public static function update(array|object $userData): int|\WP_Error
    {
        self::ensureWordPressIsLoaded();

        return wp_update_user($userData);
    }

    /**
     * Delete a user and optionally reassign their posts.
     *
     * wp_delete_user() is an administrative function and may need to be loaded
     * explicitly with wp-admin/includes/user.php outside administrative requests.
     */
    public static function delete(int $userId, ?int $reassign = null): bool
    {
        self::ensureWordPressIsLoaded();

        if (!function_exists('wp_delete_user')) {
            throw new LogicException(
                'User deletion requires wp-admin/includes/user.php to be loaded.'
            );
        }

        return wp_delete_user($userId, $reassign);
    }

    /**
     * Retrieve a user by ID.
     */
    public static function get(int $userId): \WP_User|false
    {
        self::ensureWordPressIsLoaded();

        return get_userdata($userId);
    }

    /**
     * Retrieve a user by a supported WordPress user field.
     *
     * Supported fields are id, ID, slug, email, and login.
     */
    public static function findBy(string $field, int|string $value): \WP_User|false
    {
        self::ensureWordPressIsLoaded();

        return get_user_by($field, $value);
    }

    private static function ensureWordPressIsLoaded(): void
    {
        if (!function_exists('wp_insert_user')) {
            throw new LogicException('User requires WordPress to be loaded.');
        }
    }
}
