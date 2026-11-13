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
 * A thin wrapper around the native WordPress Navigation Menu API.
 *
 * Menus are stored by WordPress as terms in the nav_menu taxonomy. This
 * wrapper exposes them as menus without leaking that storage detail.
 */
final class Menu
{
    /**
     * Create a navigation menu.
     *
     * @return int|\WP_Error The new menu term ID or a WordPress error.
     */
    public static function create(string $name): int|\WP_Error
    {
        self::ensureWordPressIsLoaded();

        return wp_create_nav_menu($name);
    }

    /**
     * Update a navigation menu's term data.
     *
     * @return array|\WP_Error The updated term data or a WordPress error.
     */
    public static function update(int $menuId, array $args): array|\WP_Error
    {
        self::ensureWordPressIsLoaded();

        return wp_update_term($menuId, 'nav_menu', $args);
    }

    /**
     * Delete a navigation menu and its menu items.
     *
     * WordPress also removes the menu from any registered theme locations.
     *
     * @return array|false|\WP_Error The native term deletion result.
     */
    public static function delete(int|string|\WP_Term $menu): array|false|\WP_Error
    {
        self::ensureWordPressIsLoaded();

        return wp_delete_nav_menu($menu);
    }

    /** Retrieve a navigation menu by ID, slug, name, or term object. */
    public static function get(int|string|\WP_Term $menu): \WP_Term|false
    {
        self::ensureWordPressIsLoaded();

        return wp_get_nav_menu_object($menu);
    }

    /**
     * Retrieve navigation menus.
     *
     * @return array<int, \WP_Term>|\WP_Error
     */
    public static function all(array $args = []): array|\WP_Error
    {
        self::ensureWordPressIsLoaded();

        return wp_get_nav_menus($args);
    }

    /**
     * Retrieve the items assigned to a navigation menu.
     *
     * The returned objects are decorated by wp_setup_nav_menu_item(); they
     * include menu properties such as title, URL, object type, and parent ID.
     *
     * @return array<int, object>|false
     */
    public static function items(int|string|\WP_Term $menu, array $args = []): array|false
    {
        self::ensureWordPressIsLoaded();

        return MenuItem::getForMenu($menu, $args);
    }

    private static function ensureWordPressIsLoaded(): void
    {
        if (!function_exists('wp_create_nav_menu')) {
            throw new LogicException('Menu requires WordPress to be loaded.');
        }
    }
}
