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
 * A thin wrapper around the native WordPress Multisite Network API.
 */
final class Network
{
    /**
     * Create a network.
     *
     * @return int|\WP_Error The network ID or a WordPress error.
     */
    public static function create(array $networkData): int|\WP_Error
    {
        self::ensureWordPressIsLoaded();

        return wp_insert_network($networkData);
    }

    /**
     * Update an existing network.
     *
     * @return int|\WP_Error The network ID or a WordPress error.
     */
    public static function update(int $networkId, array $networkData): int|\WP_Error
    {
        self::ensureWordPressIsLoaded();

        return wp_update_network($networkId, $networkData);
    }

    /**
     * Delete a network.
     */
    public static function delete(int $networkId): bool
    {
        self::ensureWordPressIsLoaded();

        return wp_delete_network($networkId);
    }

    /**
     * Retrieve a network by ID or return the supplied network object.
     */
    public static function get(int|\WP_Network|null $network): ?\WP_Network
    {
        self::ensureWordPressIsLoaded();

        return get_network($network);
    }

    private static function ensureWordPressIsLoaded(): void
    {
        if (!function_exists('wp_insert_network')) {
            throw new LogicException('Network requires WordPress Multisite to be loaded.');
        }
    }
}
