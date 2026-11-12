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
 * A thin wrapper around the native WordPress Network retrieval API.
 *
 * WordPress Core currently exposes network retrieval and querying, but no
 * equivalent native network insert, update, or delete CRUD functions.
 */
final class Network
{
    /**
     * Retrieve a network by ID or return the supplied network object.
     */
    public static function get(int|\WP_Network|null $network = null): ?\WP_Network
    {
        self::ensureWordPressIsLoaded();

        return get_network($network);
    }

    private static function ensureWordPressIsLoaded(): void
    {
        if (!function_exists('get_network')) {
            throw new LogicException('Network requires WordPress Multisite to be loaded.');
        }
    }
}
