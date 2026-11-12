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
     * @return \WP_Site|\WP_Error|false The deleted site or the native failure value.
     */
    public static function delete(int $siteId): \WP_Site|\WP_Error|false
    {
        self::ensureWordPressIsLoaded();

        return wp_delete_site($siteId);
    }

    /**
     * Retrieve a site by ID or return the supplied site object.
     */
    public static function get(int|\WP_Site|null $site): ?\WP_Site
    {
        self::ensureWordPressIsLoaded();

        return get_site($site);
    }

    private static function ensureWordPressIsLoaded(): void
    {
        if (!function_exists('wp_insert_site')) {
            throw new LogicException('Site requires WordPress Multisite to be loaded.');
        }
    }
}
