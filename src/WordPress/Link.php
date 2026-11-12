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
 * A wrapper around WordPress's legacy Links (Bookmarks) API.
 *
 * Link creation, updates, and deletion require wp-admin/includes/bookmark.php.
 */
final class Link
{
    /**
     * Create a link.
     *
     * @return int|\WP_Error The link ID or a WordPress error.
     */
    public static function create(array $linkData, bool $wpError = false): int|\WP_Error
    {
        self::ensureAdminApiIsLoaded();

        return wp_insert_link($linkData, $wpError);
    }

    /**
     * Update an existing link.
     *
     * @return int The updated link ID, or 0 on failure.
     */
    public static function update(array $linkData): int
    {
        self::ensureAdminApiIsLoaded();

        return wp_update_link($linkData);
    }

    /**
     * Delete a link.
     */
    public static function delete(int $linkId): bool
    {
        self::ensureAdminApiIsLoaded();

        return wp_delete_link($linkId);
    }

    /**
     * Retrieve a link using the native output and filter options.
     */
    public static function get(
        int|\stdClass $link,
        string $output = 'OBJECT',
        string $filter = 'raw'
    ): mixed {
        self::ensureWordPressIsLoaded();

        return get_bookmark($link, $output, $filter);
    }

    /**
     * Query links using the native get_bookmarks() arguments.
     *
     * @return array<int, mixed>
     */
    public static function query(array|string $queryArguments = []): array
    {
        self::ensureWordPressIsLoaded();

        return get_bookmarks($queryArguments);
    }

    private static function ensureWordPressIsLoaded(): void
    {
        if (!function_exists('get_bookmark')) {
            throw new LogicException('Link requires WordPress to be loaded.');
        }
    }


    /** Retrieve the category IDs assigned to a link. */
    public static function getCategoryIds(int $linkId): array
    {
        self::ensureAdminApiIsLoaded();

        return wp_get_link_cats($linkId);
    }

    /** Assign categories to a link. */
    public static function setCategories(int $linkId, array $categoryIds): void
    {
        self::ensureAdminApiIsLoaded();

        wp_set_link_cats($linkId, $categoryIds);
    }

    /** Retrieve the category name assigned to a link. */
    public static function getCategoryName(int $linkId): string
    {
        self::ensureWordPressIsLoaded();

        return get_linkcatname($linkId);
    }

    private static function ensureAdminApiIsLoaded(): void
    {
        self::ensureWordPressIsLoaded();

        if (!function_exists('wp_insert_link')) {
            throw new LogicException(
                'Link creation, updates, and deletion require wp-admin/includes/bookmark.php to be loaded.'
            );
        }
    }
}
