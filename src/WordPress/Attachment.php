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
     * @return int|\WP_Error The attachment ID or a WordPress error.
     */
    public static function update(
        array|object $attachmentData,
        bool $wpError = false,
        bool $fireAfterHooks = true
    ): int|\WP_Error {
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
    public static function updateMetadata(int $attachmentId, array $metadata): int|bool
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
     * Generate image and media metadata for an attachment.
     *
     * WordPress loads this function from wp-admin/includes/image.php.
     *
     * @return array|false Generated metadata, or false when the attachment has no file.
     */
    public static function generateMetadata(int $attachmentId): array|false
    {
        self::ensureWordPressIsLoaded();

        if (!function_exists('wp_generate_attachment_metadata')) {
            throw new LogicException(
                'Attachment metadata generation requires wp-admin/includes/image.php to be loaded.'
            );
        }

        $file = self::getFile($attachmentId);

        if (!$file) {
            return false;
        }

        return wp_generate_attachment_metadata($attachmentId, $file);
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


    /** Retrieve an attachment's MIME type. */
    public static function getMimeType(int $attachmentId): string|false
    {
        self::ensureWordPressIsLoaded();

        return get_post_mime_type($attachmentId);
    }

    /** Determine whether an attachment is an image. */
    public static function isImage(int $attachmentId): bool
    {
        self::ensureWordPressIsLoaded();

        return wp_attachment_is_image($attachmentId);
    }

    /** Retrieve the attachment's parent post ID. */
    public static function getParentId(int $attachmentId): int|false
    {
        self::ensureWordPressIsLoaded();

        return wp_get_post_parent_id($attachmentId);
    }

    /** Retrieve the attachment's image alternative text. */
    public static function getAlt(int $attachmentId): string
    {
        self::ensureWordPressIsLoaded();

        return (string) get_post_meta($attachmentId, '_wp_attachment_image_alt', true);
    }

    /** Update the attachment's image alternative text. */
    public static function updateAlt(int $attachmentId, string $alt): bool|int
    {
        self::ensureWordPressIsLoaded();

        return update_post_meta($attachmentId, '_wp_attachment_image_alt', $alt);
    }

    /** Retrieve the attachment caption. */
    public static function getCaption(int $attachmentId): string
    {
        self::ensureWordPressIsLoaded();

        return (string) get_post_field('post_excerpt', $attachmentId);
    }

    /** Retrieve the attachment description. */
    public static function getDescription(int $attachmentId): string
    {
        self::ensureWordPressIsLoaded();

        return (string) get_post_field('post_content', $attachmentId);
    }

    /** Retrieve the HTML image element for an image attachment. */
    public static function getImage(int $attachmentId, string|array $size = 'thumbnail', array $args = []): string
    {
        self::ensureWordPressIsLoaded();

        return wp_get_attachment_image($attachmentId, $size, false, $args);
    }

    /** Retrieve an image attachment's responsive srcset attribute value. */
    public static function getImageSrcset(int $attachmentId, string|array $size = 'medium'): string|false
    {
        self::ensureWordPressIsLoaded();

        return wp_get_attachment_image_srcset($attachmentId, $size);
    }

    /** Retrieve an image attachment's responsive sizes attribute value. */
    public static function getImageSizes(int $attachmentId, string|array $size = 'medium'): string|false
    {
        self::ensureWordPressIsLoaded();

        return wp_get_attachment_image_sizes($attachmentId, $size);
    }

    /** Retrieve a MIME type icon URL for an attachment. */
    public static function getIcon(int|\WP_Post $attachment, array|string|null $mime = null): string
    {
        self::ensureWordPressIsLoaded();

        return wp_mime_type_icon($attachment, $mime);
    }


    /**
     * Handle a file uploaded through a WordPress request.
     *
     * Requires wp-admin/includes/media.php to be loaded.
     *
     * @return int|\WP_Error The attachment ID or a WordPress error.
     */
    public static function handleUpload(
        string $fileId,
        int $postId = 0,
        array $postData = [],
        array $overrides = ['test_form' => false]
    ): int|\WP_Error {
        self::ensureWordPressIsLoaded();

        if (!function_exists('media_handle_upload')) {
            throw new LogicException(
                'Attachment uploads require wp-admin/includes/media.php to be loaded.'
            );
        }

        return media_handle_upload($fileId, $postId, $postData, $overrides);
    }

    /**
     * Sideload a file into the Media Library.
     *
     * Requires wp-admin/includes/media.php to be loaded.
     *
     * @return int|\WP_Error The attachment ID or a WordPress error.
     */
    public static function handleSideload(
        array $file,
        int $postId = 0,
        ?string $description = null,
        array $postData = []
    ): int|\WP_Error {
        self::ensureWordPressIsLoaded();

        if (!function_exists('media_handle_sideload')) {
            throw new LogicException(
                'Attachment sideloads require wp-admin/includes/media.php to be loaded.'
            );
        }

        return media_handle_sideload($file, $postId, $description, $postData);
    }

    private static function ensureWordPressIsLoaded(): void
    {
        if (!function_exists('wp_insert_attachment')) {
            throw new LogicException('Attachment requires WordPress to be loaded.');
        }
    }
}
