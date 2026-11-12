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

class SearchQueryArg implements QueryArg
{

    protected ?string $search = null;

    protected array $searchColumns = [];

    protected array $allowedSearchColumns;

    public function __construct(array $allowedSearchColumns, array $queryArray = [])
    {
        foreach ($allowedSearchColumns as $column) {
            if (!\is_string($column)) {
                throw new \InvalidArgumentException(
                    'Every allowed search column must be a string.'
                );
            }
        }

        $this->allowedSearchColumns = \array_values(
            \array_unique($allowedSearchColumns, SORT_STRING)
        );

        if (isset($queryArray['search'])) {
            $this->search($queryArray['search']);
        }

        if (isset($queryArray['searchColumns'])) {
            $this->searchColumns($queryArray['searchColumns']);
        }
    }

    public static function make(array $queryArray = [], array $args = []): static
    {
        if (!isset($args['allowedSearchColumns'])) {
            throw new \InvalidArgumentException(
                'Allowed search columns must be provided.'
            );
        }

        return new static(
            $args['allowedSearchColumns'],
            $queryArray
        );
    }

    public function search(string $value): self
    {
        $this->search = '' === $value ? null : $value;

        return $this;
    }

    public function searchColumns(string|array $value): self
    {
        $values = \is_array($value) ? $value : [$value];

        $values = \array_values(
            \array_unique(
                \array_filter(
                    $values,
                    function ($item): bool {
                        if (!\is_string($item)) {
                            throw new \InvalidArgumentException('Every search column must be a string.');
                        }

                        if ('' === $item) {
                            return false;
                        }

                        if (!\in_array($item, $this->allowedSearchColumns, true)) {
                            throw new \InvalidArgumentException(
                                'Invalid search column: ' . $item
                            );
                        }

                        return true;
                    }
                ),
                SORT_STRING
            )
        );

        $this->searchColumns = $values;

        return $this;
    }

    public function toArray(): array
    {
        $output = [];

        if (null !== $this->search) {
            $output['search'] = $this->search;
        }

        if ([] !== $this->searchColumns) {
            $output['searchColumns'] = $this->searchColumns;
        }

        return $output;
    }
}
