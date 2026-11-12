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

    /**
     * Check whether a term exists in a taxonomy.
     *
     * @return array|int|\WP_Error|null Native term existence result.
     */
    public static function exists(
        string|int $term,
        string $taxonomy = '',
        ?int $parent = null
    ): array|int|\WP_Error|null {
        self::ensureWordPressIsLoaded();

        return term_exists($term, $taxonomy, $parent);
    }

    /**
     * Assign terms to an object.
     *
     * @return array|\WP_Error Term taxonomy IDs or a WordPress error.
     */
    public static function assignToObject(
        int $objectId,
        array|string|int $terms,
        string $taxonomy,
        bool $append = false
    ): array|\WP_Error {
        self::ensureWordPressIsLoaded();

        return wp_set_object_terms($objectId, $terms, $taxonomy, $append);
    }

    /**
     * Retrieve terms assigned to an object.
     *
     * @return array|\WP_Error Native term results or a WordPress error.
     */
    public static function getForObject(
        int $objectId,
        string $taxonomy,
        array $args = []
    ): array|\WP_Error {
        self::ensureWordPressIsLoaded();

        return wp_get_object_terms($objectId, $taxonomy, $args);
    }

    /**
     * Remove terms from an object.
     */
    public static function removeFromObject(
        int $objectId,
        array|string|int $terms,
        string $taxonomy
    ): bool|\WP_Error {
        self::ensureWordPressIsLoaded();

        return wp_remove_object_terms($objectId, $terms, $taxonomy);
    }


    /** Retrieve a term's permalink. */
    public static function getLink(int|\WP_Term $term, string $taxonomy = ''): string|\WP_Error
    {
        self::ensureWordPressIsLoaded();

        return get_term_link($term, $taxonomy);
    }

    /** Retrieve a term's child term IDs. */
    public static function getChildren(int $termId, string $taxonomy): array|\WP_Error
    {
        self::ensureWordPressIsLoaded();

        return get_term_children($termId, $taxonomy);
    }

    /** Retrieve a term's ancestor IDs. */
    public static function getAncestors(int $termId, string $taxonomy): array
    {
        self::ensureWordPressIsLoaded();

        return get_ancestors($termId, $taxonomy, 'taxonomy');
    }

    /** Retrieve object IDs associated with a term. */
    public static function getObjectIds(int $termId, string $taxonomy, array $args = []): array|\WP_Error
    {
        self::ensureWordPressIsLoaded();

        return get_objects_in_term($termId, $taxonomy, $args);
    }

    /** Retrieve the registered taxonomy object. */
    public static function getTaxonomy(string $taxonomy): \WP_Taxonomy|false
    {
        self::ensureWordPressIsLoaded();

        return get_taxonomy($taxonomy);
    }

    /** Retrieve the number of terms in a taxonomy. */
    public static function count(string $taxonomy, array $args = []): int|string|\WP_Error
    {
        self::ensureWordPressIsLoaded();

        return wp_count_terms($taxonomy, $args);
    }

    /** Retrieve terms associated with an object, preserving native arguments. */
    public static function getForObject(int $objectId, string $taxonomy, array $args = []): array|\WP_Error
    {
        self::ensureWordPressIsLoaded();

        return wp_get_object_terms($objectId, $taxonomy, $args);
    }

    private static function ensureWordPressIsLoaded(): void
    {
        if (!function_exists('wp_insert_term')) {
            throw new LogicException('Term requires WordPress to be loaded.');
        }
    }
}
