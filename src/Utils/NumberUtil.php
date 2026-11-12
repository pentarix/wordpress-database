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

final class NumberUtil
{
    public static function absint(int|float|string $value): int
    {
        return (int) abs((float) $value);
    }
}