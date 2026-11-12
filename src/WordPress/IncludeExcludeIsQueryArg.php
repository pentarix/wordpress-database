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

use Pentarix\WordPressDatabase\Utils\ArrayUtil;
use InvalidArgumentException;

class IncludeExcludeIsQueryArg extends IncludeExcludeQueryArg
{

    protected string $isKey;

    protected bool $multiValue = false;

    protected string|array|null $is = null;

    public function __construct(
        array $queryArray = [],
        bool $isIdentifier = true,
        bool $multiValue = false,
        string $includeKey = 'include',
        string $excludeKey = 'exclude',
        string $isKey = 'is'
    ) {
        $this->multiValue = $multiValue;
        $this->isKey = $isKey;

        parent::__construct($queryArray, $isIdentifier, $includeKey, $excludeKey);

        if (isset($queryArray[$this->isKey])) {
            $this->is($queryArray[$this->isKey]);
        }
    }

    public static function make(array $queryArray = [], array $args = []): static
    {
        return new static(
            $queryArray,
            $args['isIdentifier'] ?? true,
            $args['multiValue'] ?? false,
            $args['includeKey'] ?? 'include',
            $args['excludeKey'] ?? 'exclude',
            $args['isKey'] ?? 'is'
        );
    }

    public function is(string|array $value): static
    {
        if (\is_string($value)) {
            $this->is = '' === $value ? null : $value;

            return $this;
        }

        if (!\is_array($value)) {
            throw new InvalidArgumentException('Is value must be a string or an array.');
        }

        $value = ArrayUtil::normalizeUniqueStrings($value);

        $this->is = [] === $value ? null : $value;

        return $this;
    }

    public function toArray(): array
    {
        $output = parent::toArray();

        if (null !== $this->is) {
            $output[$this->isKey] = $this->is;
        }

        return $output;
    }
}
