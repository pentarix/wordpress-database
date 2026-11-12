<?php

/**
 * @author    Sina Kuhestani <sinakuhestani@gmail.com>
 * @copyright 2026 Sina Kuhestani (EphpicMan)
 * @link      https://github.com/ephpicman GitHub
 * @link      https://www.linkedin.com/in/ephpicman LinkedIn
 * @license   See the LICENSE file in the project root.
 */

declare(strict_types=1);

namespace Pentarix\WordPressDatabase\Utils;

final class ArrayUtil
{
    public static function castValues(
        int|float|string|array $value,
        callable $cast
    ): array {
        $values = \is_array($value) ? $value : [$value];

        return \array_values(
            \array_unique(
                \array_map($cast, $values),
                SORT_REGULAR
            )
        );
    }
    public static function mergeCasted(
        array $current,
        int|float|string|array $value,
        callable $cast
    ): array {
        return \array_values(
            \array_unique(
                \array_merge(
                    $current,
                    self::castValues($value, $cast)
                ),
                SORT_REGULAR
            )
        );
    }

    public static function normalizeUniqueStrings(array $values): array
    {
        return \array_values(
            \array_unique(
                \array_filter(
                    $values,
                    static function ($value): bool {
                        if (!\is_string($value)) {
                            throw new \InvalidArgumentException(
                                'Every value must be a string.'
                            );
                        }

                        return '' !== $value;
                    }
                ),
                SORT_STRING
            )
        );
    }

    public static function mergeUnique(array ...$arrays): array
    {
        return \array_values(
            \array_unique(
                \array_merge(...$arrays),
                SORT_REGULAR
            )
        );
    }

    public static function removeValues(
        array $current,
        array $values,
        bool $strict = false
    ): array {
        if (!$strict) {
            return \array_values(
                \array_diff($current, $values)
            );
        }

        return \array_values(
            \array_filter(
                $current,
                static fn($item): bool => !\in_array($item, $values, true)
            )
        );
    }
}
