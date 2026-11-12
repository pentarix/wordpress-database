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
        string $output = OBJECT,
        string $filter = 'raw'
    ): mixed {
        self::ensureWordPressIsLoaded();

        return get_post($post, $output, $filter);
    }

    private static function ensureWordPressIsLoaded(): void
    {
        if (!function_exists('wp_insert_post')) {
            throw new LogicException('Post requires WordPress to be loaded.');
        }
    }
}
