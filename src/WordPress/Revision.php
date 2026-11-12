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
    public static function save(int $postId): int|\WP_Post|false
    {
        self::ensureWordPressIsLoaded();

        return wp_save_post_revision($postId);
    }

    /**
     * Retrieve a revision by ID.
     */
    public static function get(int $revisionId): \WP_Post|false
    {
        self::ensureWordPressIsLoaded();

        return wp_get_post_revision($revisionId);
    }

    /**
     * Retrieve revisions belonging to a post.
     *
     * @return array<int, \WP_Post>
     */
    public static function all(int $postId, array $queryArguments = []): array
    {
        self::ensureWordPressIsLoaded();

        return wp_get_post_revisions($postId, $queryArguments);
    }

    /**
     * Restore a revision to its parent post.
     */
    public static function restore(int $revisionId): bool|int|\WP_Error
    {
        self::ensureWordPressIsLoaded();

        return wp_restore_post_revision($revisionId);
    }

    /**
     * Delete a revision.
     */
    public static function delete(int $revisionId): \WP_Post|false|null
    {
        self::ensureWordPressIsLoaded();

        return wp_delete_post_revision($revisionId);
    }

    private static function ensureWordPressIsLoaded(): void
    {
        if (!function_exists('wp_get_post_revisions')) {
            throw new LogicException('Revision requires WordPress to be loaded.');
        }
    }
}
