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
 * A thin wrapper around the native WordPress Comment API.
 */
final class Comment
{
    /**
     * Create a comment.
     */
    public static function create(array $commentData): int|false
    {
        self::ensureWordPressIsLoaded();

        return wp_insert_comment($commentData);
    }

    /**
     * Update a comment.
     *
     * The native function may return an integer, true, or WP_Error depending
     * on the operation and the requested error handling.
     */
    public static function update(array $commentData, bool $wpError = false): mixed
    {
        self::ensureWordPressIsLoaded();

        return wp_update_comment($commentData, $wpError);
    }

    /**
     * Delete or trash a comment.
     */
    public static function delete(int $commentId, bool $forceDelete = false): bool
    {
        self::ensureWordPressIsLoaded();

        return wp_delete_comment($commentId, $forceDelete);
    }

    /**
     * Retrieve a comment using the native WordPress output format.
     */
    public static function get(int|object|null $comment, string $output = 'OBJECT'): mixed
    {
        self::ensureWordPressIsLoaded();

        return get_comment($comment, $output);
    }

    private static function ensureWordPressIsLoaded(): void
    {
        if (!function_exists('wp_insert_comment')) {
            throw new LogicException('Comment requires WordPress to be loaded.');
        }
    }
}
