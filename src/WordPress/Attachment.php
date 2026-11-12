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
 * A convenience API for WordPress attachments, which are stored as posts.
 */
final class Attachment
{
    /**
     * Insert an attachment using the native WordPress API.
     *
     * The file argument is a path accepted by wp_insert_attachment().
     *
     * @return int|\WP_Error The attachment ID or a WordPress error.
     */
    public static function create(
        array $attachmentData,
        string|false $file = false,
        int $parentPostId = 0,
        bool $wpError = false
    ): int|\WP_Error {
        self::ensureWordPressIsLoaded();

        return wp_insert_attachment($attachmentData, $file, $parentPostId, $wpError);
    }

    /**
     * Update the attachment's post fields.
     *
     * @return int|\\WP_Error The attachment ID or a WordPress error.
     */
    public static function update(
        array|object $attachmentData,
        bool $wpError = false,
        bool $fireAfterHooks = true
    ): int|\\WP_Error {
        self::ensureWordPressIsLoaded();

        return wp_update_post($attachmentData, $wpError, $fireAfterHooks);
    }

    /**
     * Retrieve an attachment as a native post object or requested output.
     */
    public static function get(
        int|\WP_Post|null $attachment,
        string $output = 'OBJECT',
        string $filter = 'raw'
    ): mixed {
        self::ensureWordPressIsLoaded();

        return get_post($attachment, $output, $filter);
    }

    /**
     * Delete an attachment and its generated metadata and files.
     */
    public static function delete(int $attachmentId, bool $forceDelete = false): \WP_Post|false|null
    {
        self::ensureWordPressIsLoaded();

        return wp_delete_attachment($attachmentId, $forceDelete);
    }

    /**
     * Retrieve the attachment metadata array.
     */
    public static function getMetadata(int $attachmentId): array|false
    {
        self::ensureWordPressIsLoaded();

        return wp_get_attachment_metadata($attachmentId);
    }

    /**
     * Update the attachment metadata array.
     */
    public static function updateMetadata(int $attachmentId, array $metadata): bool
    {
        self::ensureWordPressIsLoaded();

        return wp_update_attachment_metadata($attachmentId, $metadata);
    }

    /**
     * Retrieve the attached file path.
     */
    public static function getFile(int $attachmentId, bool $unfiltered = false): string|false
    {
        self::ensureWordPressIsLoaded();

        return get_attached_file($attachmentId, $unfiltered);
    }

    /**
     * Update the attached file path.
     */
    public static function updateFile(int $attachmentId, string $file): bool
    {
        self::ensureWordPressIsLoaded();

        return update_attached_file($attachmentId, $file);
    }

    /**
     * Retrieve the attachment URL.
     */
    public static function getUrl(int $attachmentId): string|false
    {
        self::ensureWordPressIsLoaded();

        return wp_get_attachment_url($attachmentId);
    }

    /**
     * Retrieve an image source for an attachment.
     *
     * @return array{0:string,1:int,2:int,3:bool}|false
     */
    public static function getImageSource(
        int $attachmentId,
        string|array $size = 'thumbnail',
        bool $icon = false
    ): array|false {
        self::ensureWordPressIsLoaded();

        return wp_get_attachment_image_src($attachmentId, $size, $icon);
    }

    private static function ensureWordPressIsLoaded(): void
    {
        if (!function_exists('wp_insert_attachment')) {
            throw new LogicException('Attachment requires WordPress to be loaded.');
        }
    }
}
