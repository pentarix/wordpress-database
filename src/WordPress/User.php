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


    /** Retrieve the current user. */
    public static function getCurrent(): \WP_User
    {
        self::ensureWordPressIsLoaded();

        return wp_get_current_user();
    }

    /** Retrieve the current user ID. */
    public static function getCurrentId(): int
    {
        self::ensureWordPressIsLoaded();

        return get_current_user_id();
    }

    /** Check whether a user exists. */
    public static function exists(int|string $user): bool
    {
        self::ensureWordPressIsLoaded();

        if (is_int($user)) {
            return (bool) get_userdata($user);
        }

        return (bool) get_user_by('login', $user);
    }

    /** Retrieve a user by email address. */
    public static function findByEmail(string $email): \WP_User|false
    {
        self::ensureWordPressIsLoaded();

        return get_user_by('email', $email);
    }

    /** Retrieve a user's roles. */
    public static function getRoles(int|\WP_User $user): array
    {
        self::ensureWordPressIsLoaded();

        $user = $user instanceof \WP_User ? $user : get_userdata($user);

        return $user instanceof \WP_User ? $user->roles : [];
    }

    /** Assign a role to a user, replacing their existing roles. */
    public static function setRole(int|\WP_User $user, string $role): void
    {
        self::ensureWordPressIsLoaded();

        $user = $user instanceof \WP_User ? $user : new \WP_User($user);
        $user->set_role($role);
    }

    /** Add a role without removing existing roles. */
    public static function addRole(int|\WP_User $user, string $role): void
    {
        self::ensureWordPressIsLoaded();

        $user = $user instanceof \WP_User ? $user : new \WP_User($user);
        $user->add_role($role);
    }

    /** Remove a role from a user. */
    public static function removeRole(int|\WP_User $user, string $role): void
    {
        self::ensureWordPressIsLoaded();

        $user = $user instanceof \WP_User ? $user : new \WP_User($user);
        $user->remove_role($role);
    }

    /** Check a user's capability, including meta capabilities. */
    public static function can(int|\WP_User $user, string $capability, mixed ...$args): bool
    {
        self::ensureWordPressIsLoaded();

        $user = $user instanceof \WP_User ? $user : new \WP_User($user);

        return $user->has_cap($capability, ...$args);
    }

    /** Set a user's password using WordPress's native password handling. */
    public static function setPassword(string $password, int $userId): void
    {
        self::ensureWordPressIsLoaded();

        wp_set_password($password, $userId);
    }

    /** Verify a password against a user's stored password hash. */
    public static function checkPassword(string $password, int|\WP_User $user): bool
    {
        self::ensureWordPressIsLoaded();

        $user = $user instanceof \WP_User ? $user : get_userdata($user);

        return $user instanceof \WP_User && wp_check_password($password, $user->user_pass, $user->ID);
    }

    /** Determine whether a user belongs to a site on a Multisite network. */
    public static function isMemberOfSite(int $userId, int $siteId = 0): bool
    {
        self::ensureWordPressIsLoaded();

        return is_user_member_of_blog($userId, $siteId);
    }

    /** Add a user to a site on a Multisite network. */
    public static function addToSite(int $userId, int $siteId, string $role = 'subscriber'): bool|\WP_Error
    {
        self::ensureWordPressIsLoaded();

        return add_user_to_blog($siteId, $userId, $role);
    }

    /** Remove a user from a site on a Multisite network. */
    public static function removeFromSite(int $userId, int $siteId): bool|\WP_Error
    {
        self::ensureWordPressIsLoaded();

        return remove_user_from_blog($userId, $siteId);
    }

    private static function ensureWordPressIsLoaded(): void
    {
        if (!function_exists('wp_insert_user')) {
            throw new LogicException('User requires WordPress to be loaded.');
        }
    }
}
