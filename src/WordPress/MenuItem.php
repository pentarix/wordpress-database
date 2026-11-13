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
 * A thin wrapper around the native WordPress Navigation Menu Item API.
 *
 * WordPress stores menu items as nav_menu_item posts and decorates them when
 * retrieving a menu. This class delegates creation and updates to Core.
 */
final class MenuItem
{
    /**
     * Create a menu item.
     *
     * @return int|\WP_Error The new menu item post ID or a WordPress error.
     */
    public static function create(
        int $menuId,
        array $args,
        bool $fireAfterHooks = true
    ): int|\WP_Error {
        self::ensureWordPressIsLoaded();

        return wp_update_nav_menu_item($menuId, 0, $args, $fireAfterHooks);
    }

    /**
     * Update an existing menu item.
     *
     * @return int|\WP_Error The menu item post ID or a WordPress error.
     */
    public static function update(
        int $menuId,
        int $itemId,
        array $args,
        bool $fireAfterHooks = true
    ): int|\WP_Error {
        self::ensureWordPressIsLoaded();

        return wp_update_nav_menu_item($menuId, $itemId, $args, $fireAfterHooks);
    }

    /**
     * Delete a menu item permanently.
     *
     * A menu item is always permanently deleted; it is not moved to the Trash.
     */
    public static function delete(int $itemId): \WP_Post|false|null
    {
        self::ensureWordPressIsLoaded();

        if (!is_nav_menu_item($itemId)) {
            return false;
        }

        return wp_delete_post($itemId, true);
    }

    /** Retrieve a raw menu item post, or null when it does not exist. */
    public static function get(int $itemId): ?\WP_Post
    {
        self::ensureWordPressIsLoaded();

        $item = get_post($itemId);

        return $item instanceof \WP_Post && 'nav_menu_item' === $item->post_type
            ? $item
            : null;
    }

    /**
     * Retrieve the decorated menu items assigned to a menu.
     *
     * @return array<int, object>|false
     */
    public static function getForMenu(int|string|\WP_Term $menu, array $args = []): array|false
    {
        self::ensureWordPressIsLoaded();

        return wp_get_nav_menu_items($menu, $args);
    }

    private static function ensureWordPressIsLoaded(): void
    {
        if (!function_exists('wp_update_nav_menu_item')) {
            throw new LogicException('MenuItem requires WordPress to be loaded.');
        }
    }
}
