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


    /** Change a comment's status using the native status transition API. */
    public static function setStatus(int $commentId, string $status, bool $wpError = false): bool|\WP_Error
    {
        self::ensureWordPressIsLoaded();

        return wp_set_comment_status($commentId, $status, $wpError);
    }

    /** Approve a comment. */
    public static function approve(int $commentId): bool|\WP_Error
    {
        self::ensureWordPressIsLoaded();

        return wp_set_comment_status($commentId, 'approve', true);
    }

    /** Hold a comment for moderation. */
    public static function hold(int $commentId): bool|\WP_Error
    {
        self::ensureWordPressIsLoaded();

        return wp_set_comment_status($commentId, 'hold', true);
    }

    /** Mark a comment as spam. */
    public static function spam(int $commentId): bool|\WP_Error
    {
        self::ensureWordPressIsLoaded();

        return wp_spam_comment($commentId);
    }

    /** Remove a comment from the spam queue. */
    public static function unspam(int $commentId): bool|\WP_Error
    {
        self::ensureWordPressIsLoaded();

        return wp_unspam_comment($commentId);
    }

    /** Move a comment to the Trash. */
    public static function trash(int $commentId): bool
    {
        self::ensureWordPressIsLoaded();

        return wp_trash_comment($commentId);
    }

    /** Restore a trashed comment. */
    public static function restore(int $commentId): bool
    {
        self::ensureWordPressIsLoaded();

        return wp_untrash_comment($commentId);
    }

    /** Retrieve a comment's direct replies. */
    public static function getChildren(int $commentId, array $args = []): array
    {
        self::ensureWordPressIsLoaded();

        return get_comments(array_merge(['parent' => $commentId], $args));
    }

    /** Create a reply to an existing comment. */
    public static function reply(int $commentId, array $commentData, bool $wpError = false): int|bool|\WP_Error
    {
        self::ensureWordPressIsLoaded();

        $parent = get_comment($commentId);

        if (!$parent) {
            return new \WP_Error('invalid_comment', 'The parent comment does not exist.');
        }

        $commentData['comment_post_ID'] = (int) $parent->comment_post_ID;
        $commentData['comment_parent'] = $commentId;

        return wp_new_comment($commentData, $wpError);
    }

    /** Retrieve a post's comment counts by status. */
    public static function countForPost(int $postId): array
    {
        self::ensureWordPressIsLoaded();

        return get_comment_count($postId);
    }

    /** Retrieve a comment author's avatar URL. */
    public static function getAvatarUrl(int|\WP_Comment $comment, array $args = []): string|false
    {
        self::ensureWordPressIsLoaded();

        $comment = $comment instanceof \WP_Comment ? $comment : get_comment($comment);

        return $comment ? get_avatar_url($comment, $args) : false;
    }

    private static function ensureWordPressIsLoaded(): void
    {
        if (!function_exists('wp_insert_comment')) {
            throw new LogicException('Comment requires WordPress to be loaded.');
        }
    }
}
