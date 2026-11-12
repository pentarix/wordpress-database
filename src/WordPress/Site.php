<?php

/**
 * @author    Sina Kuhestani <sinakuhestani@gmail.com>
 * @copyright 2026 Sina Kuhestani (EphpicMan)
 * @link      https://github.com/ephpicman GitHub
 * @link      https://www.linkedin.com/in/ephpicman LinkedIn
 * @license   See the LICENSE file in the project root.
 */

declare(strict_types=1);

namespace Pentarix\WordPressDatabase\WordPress;

use LogicException;

/**
 * A thin wrapper around the native WordPress Multisite Site API.
 */
final class Site
{
    /**
     * Create a site in the current network.
     *
     * @return int|\WP_Error The site ID or a WordPress error.
     */
    public static function create(array $siteData): int|\WP_Error
    {
        self::ensureWordPressIsLoaded();

        return wp_insert_site($siteData);
    }

    /**
     * Update an existing site.
     *
     * @return int|\WP_Error The site ID or a WordPress error.
     */
    public static function update(int $siteId, array $siteData): int|\WP_Error
    {
        self::ensureWordPressIsLoaded();

        return wp_update_site($siteId, $siteData);
    }

    /**
     * Delete a site and its site-specific data.
     *
     * @return \WP_Site|\WP_Error The deleted site or a WordPress error.
     */
    public static function delete(int $siteId): \WP_Site|\WP_Error
    {
        self::ensureWordPressIsLoaded();

        return wp_delete_site($siteId);
    }

    /**
     * Retrieve a site by ID or return the supplied site object.
     */
    public static function get(int|\WP_Site|null $site = null): ?\WP_Site
    {
        self::ensureWordPressIsLoaded();

        return get_site($site);
    }


    /** Retrieve the current site in the active blog context. */
    public static function getCurrent(): ?\WP_Site
    {
        self::ensureWordPressIsLoaded();

        return get_site(get_current_blog_id());
    }

    /** Retrieve the current site ID. */
    public static function getCurrentId(): int
    {
        self::ensureWordPressIsLoaded();

        return get_current_blog_id();
    }

    /** Switch execution to another site. */
    public static function switchTo(int $siteId): bool
    {
        self::ensureWordPressIsLoaded();

        return switch_to_blog($siteId);
    }

    /** Restore the previous site context. */
    public static function restoreCurrent(): bool
    {
        self::ensureWordPressIsLoaded();

        return restore_current_blog();
    }

    /** Determine whether the current request has switched site context. */
    public static function isSwitched(): bool
    {
        self::ensureWordPressIsLoaded();

        return ms_is_switched();
    }

    /** Retrieve a site's home URL. */
    public static function getHomeUrl(int $siteId, string $path = '', ?string $scheme = null): string
    {
        self::ensureWordPressIsLoaded();

        return get_home_url($siteId, $path, $scheme);
    }

    /** Retrieve a site's WordPress URL. */
    public static function getSiteUrl(int $siteId, string $path = '', ?string $scheme = null): string
    {
        self::ensureWordPressIsLoaded();

        return get_site_url($siteId, $path, $scheme);
    }

    /** Retrieve a site's administrative URL. */
    public static function getAdminUrl(int $siteId, string $path = '', string $scheme = 'admin'): string
    {
        self::ensureWordPressIsLoaded();

        return get_admin_url($siteId, $path, $scheme);
    }

    /** Retrieve an option belonging to a specific site. */
    public static function getOption(int $siteId, string $option, mixed $default = false): mixed
    {
        self::ensureWordPressIsLoaded();

        return get_blog_option($siteId, $option, $default);
    }

    /** Update an option belonging to a specific site. */
    public static function updateOption(int $siteId, string $option, mixed $value, mixed $deprecated = ''): bool
    {
        self::ensureWordPressIsLoaded();

        return update_blog_option($siteId, $option, $value, $deprecated);
    }

    /** Delete an option belonging to a specific site. */
    public static function deleteOption(int $siteId, string $option): bool
    {
        self::ensureWordPressIsLoaded();

        return delete_blog_option($siteId, $option);
    }

    private static function ensureWordPressIsLoaded(): void
    {
        if (!function_exists('wp_insert_site')) {
            throw new LogicException('Site requires WordPress Multisite to be loaded.');
        }
    }
}
