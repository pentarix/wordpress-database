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
 * A thin, object-type-aware wrapper around the native WordPress Metadata API.
 */
final class Metadata
{
    public static function add(string $metaType, int $objectId, string $metaKey, mixed $metaValue, bool $unique = false): int|false
    {
        self::ensureWordPressIsLoaded();

        return add_metadata($metaType, $objectId, $metaKey, $metaValue, $unique);
    }

    public static function update(
        string $metaType,
        int $objectId,
        string $metaKey,
        mixed $metaValue,
        mixed $previousValue = ''
    ): int|bool
    {
        self::ensureWordPressIsLoaded();

        return update_metadata($metaType, $objectId, $metaKey, $metaValue, $previousValue);
    }

    public static function delete(
        string $metaType,
        int $objectId,
        string $metaKey,
        mixed $metaValue = '',
        bool $deleteAll = false
    ): bool
    {
        self::ensureWordPressIsLoaded();

        return delete_metadata($metaType, $objectId, $metaKey, $metaValue, $deleteAll);
    }

    public static function get(
        string $metaType,
        int $objectId,
        string $metaKey = '',
        bool $single = false
    ): mixed
    {
        self::ensureWordPressIsLoaded();

        return get_metadata($metaType, $objectId, $metaKey, $single);
    }

    public static function exists(string $metaType, int $objectId, string $metaKey): bool
    {
        self::ensureWordPressIsLoaded();

        return metadata_exists($metaType, $objectId, $metaKey);
    }

    public static function register(
        string $metaType,
        string $metaKey,
        array $args,
        string $objectSubtype = ''
    ): bool
    {
        self::ensureWordPressIsLoaded();

        $args['object_subtype'] = $objectSubtype;

        return register_meta($metaType, $metaKey, $args);
    }

    public static function unregister(string $metaType, string $metaKey, string $objectSubtype = ''): bool
    {
        self::ensureWordPressIsLoaded();

        return unregister_meta_key($metaType, $metaKey, $objectSubtype);
    }

    public static function getById(string $metaType, int $metaId): ?object
    {
        self::ensureWordPressIsLoaded();

        return get_metadata_by_mid($metaType, $metaId) ?: null;
    }

    public static function updateById(string $metaType, int $metaId, mixed $metaValue, string|false $metaKey = false): bool
    {
        self::ensureWordPressIsLoaded();

        return update_metadata_by_mid($metaType, $metaId, $metaValue, $metaKey);
    }

    public static function deleteById(string $metaType, int $metaId): bool
    {
        self::ensureWordPressIsLoaded();

        return delete_metadata_by_mid($metaType, $metaId);
    }

    private static function ensureWordPressIsLoaded(): void
    {
        if (!function_exists('get_metadata')) {
            throw new LogicException('Metadata requires WordPress to be loaded.');
        }
    }
}
