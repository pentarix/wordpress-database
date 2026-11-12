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
 * A thin wrapper around the native WordPress Post API.
 *
 * The API covers every registered post type, including pages and custom post
 * types. It deliberately returns native WordPress values rather than models.
 */
final class Post
{
    /**
     * Create a post of any registered post type.
     *
     * @return int|\WP_Error The post ID or a WordPress error when requested.
     */
    public static function create(
        array $postData,
        bool $wpError = false,
        bool $fireAfterHooks = true
    ): int|\WP_Error {
        self::ensureWordPressIsLoaded();

        return wp_insert_post($postData, $wpError, $fireAfterHooks);
    }

    /**
     * Update an existing post.
     *
     * @return int|\WP_Error The post ID or a WordPress error when requested.
     */
    public static function update(
        array|object $postData,
        bool $wpError = false,
        bool $fireAfterHooks = true
    ): int|\WP_Error {
        self::ensureWordPressIsLoaded();

        return wp_update_post($postData, $wpError, $fireAfterHooks);
    }

    /**
     * Trash or permanently delete a post.
     *
     * WordPress decides whether a post is moved to the Trash based on its
     * post type, status, and the site's Trash configuration.
     */
    public static function delete(int $postId, bool $forceDelete = false): \WP_Post|false|null
    {
        self::ensureWordPressIsLoaded();

        return wp_delete_post($postId, $forceDelete);
    }

    /**
     * Retrieve a post using the native WordPress output and filter options.
     */
    public static function get(
        int|\WP_Post|null $post,
        string $output = 'OBJECT',
        string $filter = 'raw'
    ): mixed {
        self::ensureWordPressIsLoaded();

        return get_post($post, $output, $filter);
    }


    /** Retrieve a post's permalink. */
    public static function getPermalink(int|\WP_Post $post, bool $leavename = false): string|false
    {
        self::ensureWordPressIsLoaded();

        return get_permalink($post, $leavename);
    }

    /** Retrieve a post field using WordPress's native formatting rules. */
    public static function getField(string $field, int|\WP_Post $post, string $context = 'display'): mixed
    {
        self::ensureWordPressIsLoaded();

        return get_post_field($field, $post, $context);
    }

    /** Retrieve a post's title. */
    public static function getTitle(int|\WP_Post $post): string
    {
        self::ensureWordPressIsLoaded();

        return get_the_title($post);
    }

    /** Retrieve a post's content. */
    public static function getContent(int|\WP_Post $post): string|false
    {
        self::ensureWordPressIsLoaded();

        return get_post_field('post_content', $post);
    }

    /** Retrieve a post's excerpt. */
    public static function getExcerpt(int|\WP_Post $post): string
    {
        self::ensureWordPressIsLoaded();

        return get_the_excerpt($post);
    }

    /** Retrieve a post's status. */
    public static function getStatus(int|\WP_Post $post): string|false
    {
        self::ensureWordPressIsLoaded();

        return get_post_status($post);
    }

    /** Retrieve a post's registered post type. */
    public static function getType(int|\WP_Post $post): string|false
    {
        self::ensureWordPressIsLoaded();

        return get_post_type($post);
    }

    /** Retrieve a post's parent ID. */
    public static function getParentId(int|\WP_Post $post): int|false
    {
        self::ensureWordPressIsLoaded();

        return wp_get_post_parent_id($post);
    }

    /** Retrieve a post's ancestor IDs. */
    public static function getAncestors(int|\WP_Post $post): array
    {
        self::ensureWordPressIsLoaded();

        return get_post_ancestors($post);
    }

    /** Retrieve child posts using the native get_children() arguments. */
    public static function getChildren(int|\WP_Post $post, array|string $args = []): array
    {
        self::ensureWordPressIsLoaded();

        $args = wp_parse_args($args);
        $args['post_parent'] = (int) (is_object($post) ? $post->ID : $post);

        return get_children($args);
    }

    /** Retrieve a post's featured image attachment ID. */
    public static function getThumbnailId(int|\WP_Post $post): int|false
    {
        self::ensureWordPressIsLoaded();

        return get_post_thumbnail_id($post);
    }

    /** Set a post's featured image. */
    public static function setThumbnail(int|\WP_Post $post, int $attachmentId): bool|int
    {
        self::ensureWordPressIsLoaded();

        return set_post_thumbnail($post, $attachmentId);
    }

    /** Remove a post's featured image. */
    public static function deleteThumbnail(int|\WP_Post $post): bool
    {
        self::ensureWordPressIsLoaded();

        return delete_post_thumbnail($post);
    }

    /** Determine whether a post has a featured image. */
    public static function hasThumbnail(int|\WP_Post $post): bool
    {
        self::ensureWordPressIsLoaded();

        return has_post_thumbnail($post);
    }

    private static function ensureWordPressIsLoaded(): void
    {
        if (!function_exists('wp_insert_post')) {
            throw new LogicException('Post requires WordPress to be loaded.');
        }
    }
}
