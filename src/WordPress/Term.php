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
 * A thin wrapper around the native WordPress Term API.
 */
final class Term
{
    /**
     * Create a term in a registered taxonomy.
     *
     * @return array|\WP_Error The term IDs or a WordPress error.
     */
    public static function create(
        string $name,
        string $taxonomy,
        array|string $args = []
    ): array|\WP_Error {
        self::ensureWordPressIsLoaded();

        return wp_insert_term($name, $taxonomy, $args);
    }

    /**
     * Update a term in a registered taxonomy.
     *
     * @return array|\WP_Error The term IDs or a WordPress error.
     */
    public static function update(
        int $termId,
        string $taxonomy,
        array|string $args = []
    ): array|\WP_Error {
        self::ensureWordPressIsLoaded();

        return wp_update_term($termId, $taxonomy, $args);
    }

    /**
     * Delete a term from a registered taxonomy.
     *
     * @return bool|int|\WP_Error WordPress's native deletion result.
     */
    public static function delete(
        int $termId,
        string $taxonomy,
        array|string $args = []
    ): bool|int|\WP_Error {
        self::ensureWordPressIsLoaded();

        return wp_delete_term($termId, $taxonomy, $args);
    }

    /**
     * Retrieve a term using the native WordPress output and filter options.
     */
    public static function get(
        int|object $term,
        string $taxonomy = '',
        string $output = 'OBJECT',
        string $filter = 'raw'
    ): mixed {
        self::ensureWordPressIsLoaded();

        return get_term($term, $taxonomy, $output, $filter);
    }

    /**
     * Retrieve a term by name, slug, or ID.
     */
    public static function findBy(
        string $field,
        string|int $value,
        string $taxonomy = ''
    ): mixed {
        self::ensureWordPressIsLoaded();

        return get_term_by($field, $value, $taxonomy);
    }

    private static function ensureWordPressIsLoaded(): void
    {
        if (!function_exists('wp_insert_term')) {
            throw new LogicException('Term requires WordPress to be loaded.');
        }
    }
}
