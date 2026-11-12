<?php

/**
 * @author    Sina Kuhestani <sinakuhestani@gmail.com>
 * @copyright 2026 Sina Kuhestani (EphpicMan)
 * @link      https://github.com/ephpicman GitHub
 * @link      https://www.linkedin.com/in/ephpicman LinkedIn
 * @license   See the LICENSE file in the project root.
 */

declare(strict_types=1);

namespace Pentarix\\WordPressDatabase\\WordPress;

use Pentarix\\WordPressDatabase\\Utils\\ArrayUtil;

class IncludeExcludeQueryArg extends IncludeQueryArg
{

    protected string $excludeKey;

    protected array $excluded = [];

    public function __construct(
        array $queryArray = [],
        bool $isIdentifier = true,
        string $includeKey = 'include',
        string $excludeKey = 'exclude'
    ) {
        parent::__construct($queryArray, $isIdentifier, $includeKey);

        $this->excludeKey = $excludeKey;

        if (isset($queryArray[$this->excludeKey])) {
            $this->exclude($queryArray[$this->excludeKey]);
        }
    }

    public static function make(array $queryArray = [], array $args = []): static
    {
        return new static(
            $queryArray,
            $args['isIdentifier'] ?? true,
            $args['includeKey'] ?? 'include',
            $args['excludeKey'] ?? 'exclude'
        );
    }

    public function include(int|float|string|array $value): static
    {
        if ($this->isIdentifier && \\is_string($value) && str_contains($value, ',')) {
            $value = array_map('trim', explode(',', $value));
        }

        $values = ArrayUtil::castValues($value, fn($item) => $this->cast($item));

        $this->mergeIncluded($values);
        $this->excluded = ArrayUtil::removeValues($this->excluded, $values);

        return $this;
    }

    public function exclude(int|float|string|array $value): static
    {
        if ($this->isIdentifier && \\is_string($value) && str_contains($value, ',')) {
            $value = array_map('trim', explode(',', $value));
        }

        $values = ArrayUtil::castValues($value, fn($item) => $this->cast($item));

        $this->excluded = ArrayUtil::mergeUnique($this->excluded, $values);
        $this->included = ArrayUtil::removeValues($this->included, $values);

        return $this;
    }

    public function toArray(): array
    {
        $output = parent::toArray();

        if ([] !== $this->excluded) {
            $output[$this->excludeKey] = $this->excluded;
        }

        return $output;
    }
}
