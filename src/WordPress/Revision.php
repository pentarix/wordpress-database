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
 * A thin wrapper around the native WordPress Post Revision API.
 */
final class Revision
{
    /**
     * Save a revision for a post when revisions are supported and enabled.
     */
    public static function save(int $postId): int|\WP_Error|null
    {
        self::ensureWordPressIsLoaded();

        return wp_save_post_revision($postId);
    }

    /**
     * Retrieve a revision using the native output and filter options.
     */
    public static function get(
        int|\WP_Post $revision,
        string $output = 'OBJECT',
        string $filter = 'raw'
    ): \WP_Post|array|null {
        self::ensureWordPressIsLoaded();

        return wp_get_post_revision($revision, $output, $filter);
    }

    /**
     * Retrieve revisions belonging to a post.
     *
     * @return array<int, \WP_Post|int>
     */
    public static function all(int|\WP_Post $post, ?array $queryArguments = null): array
    {
        self::ensureWordPressIsLoaded();

        return wp_get_post_revisions($post, $queryArguments);
    }

    /**
     * Restore a revision, optionally limiting the fields restored.
     */
    public static function restore(int|\WP_Post $revision, ?array $fields = null): int|false|null
    {
        self::ensureWordPressIsLoaded();

        return wp_restore_post_revision($revision, $fields);
    }

    /**
     * Delete a revision.
     */
    public static function delete(int|\WP_Post $revision): \WP_Post|false|null
    {
        self::ensureWordPressIsLoaded();

        return wp_delete_post_revision($revision);
    }

    /**
     * Retrieve the revisions interface URL for a post or revision.
     */
    public static function getUrl(int|\WP_Post $post): ?string
    {
        self::ensureWordPressIsLoaded();

        return wp_get_post_revisions_url($post);
    }

    /**
     * Determine whether a post is a revision.
     */
    public static function isRevision(int|\WP_Post $post): int|false
    {
        self::ensureWordPressIsLoaded();

        return wp_is_post_revision($post);
    }

    /**
     * Determine whether a post is an autosave revision.
     */
    public static function isAutosave(int|\WP_Post $post): int|false
    {
        self::ensureWordPressIsLoaded();

        return wp_is_post_autosave($post);
    }


    /** Retrieve the most recent revision for a post. */
    public static function getLatest(int|\WP_Post $post): \WP_Post|false
    {
        self::ensureWordPressIsLoaded();

        $revisions = wp_get_post_revisions($post, ['posts_per_page' => 1, 'order' => 'DESC']);

        return $revisions ? reset($revisions) : false;
    }

    /** Retrieve a post's autosave revision for a specific user. */
    public static function getAutosave(int|\WP_Post $post, int $userId = 0): \WP_Post|false
    {
        self::ensureWordPressIsLoaded();

        return wp_get_post_autosave($post, $userId);
    }

    private static function ensureWordPressIsLoaded(): void
    {
        if (!function_exists('wp_get_post_revisions')) {
            throw new LogicException('Revision requires WordPress to be loaded.');
        }
    }
}
