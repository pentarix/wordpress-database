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
     * The supplied name is an ordinary, unslashed string.
     *
     * @return int|\WP_Error The new menu ID or a WordPress error.
     */
    public static function create(string $name): int|\WP_Error
    {
        self::ensureWordPressIsLoaded();

        return wp_create_nav_menu(wp_slash($name));
    }

    /**
     * Update a navigation menu.
     *
     * Accepts unslashed menu data. The convenient `name` key is mapped to
     * WordPress's native `menu-name` key; all other native keys pass through.
     *
     * @return int|\WP_Error The updated menu ID or a WordPress error.
     */
    public static function update(int $menuId, array $args): int|\WP_Error
    {
        self::ensureWordPressIsLoaded();

        if (array_key_exists('name', $args)) {
            $args['menu-name'] = $args['menu-name'] ?? $args['name'];
            unset($args['name']);
        }

        if (!array_key_exists('menu-name', $args)) {
            throw new \InvalidArgumentException('A menu name is required to update a navigation menu.');
        }

        return wp_update_nav_menu_object($menuId, wp_slash($args));
    }

    /**
     * Delete a navigation menu and its menu items.
     *
     * WordPress also removes the menu from any registered theme locations.
     *
     * @return bool|\WP_Error True on success, false when the menu does not exist.
     */
    public static function delete(int|string|\WP_Term $menu): bool|\WP_Error
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
