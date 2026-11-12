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
use Pentarix\WordPressDatabase\Utils\NumberUtil;

class IncludeQueryArg implements QueryArg
{

    protected bool $isIdentifier = true;

    protected string $includeKey;

    protected array $included = [];

    public function __construct(
        array $queryArray = [],
        bool $isIdentifier = true,
        string $includeKey = 'included'
    ) {
        $this->isIdentifier = $isIdentifier;
        $this->includeKey = $includeKey;

        if (isset($queryArray[$this->includeKey])) {
            $this->include($queryArray[$this->includeKey]);
        }
    }

    public static function make(array $queryArray = [], array $args = []): static
    {
        return new static(
            $queryArray,
            $args['isIdentifier'] ?? true,
            $args['includeKey'] ?? 'included'
        );
    }

    protected function cast(int|float|string $value): int|string
    {
        return true === $this->isIdentifier ? NumberUtil::absint($value) : (string) $value;
    }

    protected function normaliseInput(int|float|string|array $value): int|float|string|array
    {
        if (!$this->isIdentifier || !\is_string($value)) {
            return $value;
        }

        $value = \trim($value);

        if ('' === $value) {
            return [];
        }

        if (\preg_match('/[,\\s]/', $value)) {
            return \preg_split('/[,\\s]+/', $value, -1, PREG_SPLIT_NO_EMPTY) ?: [];
        }

        return $value;
    }

    public function include(int|float|string|array $value): static
    {
        $value = $this->normaliseInput($value);

        $this->mergeIncluded(
            ArrayUtil::castValues($value, fn($item) => $this->cast($item))
        );

        return $this;
    }

    public function toArray(): array
    {
        if ([] === $this->included) {
            return [];
        }

        return [
            $this->includeKey => $this->included,
        ];
    }

    protected function mergeIncluded(array $values): void
    {
        $this->included = ArrayUtil::mergeUnique($this->included, $values);
    }
}
