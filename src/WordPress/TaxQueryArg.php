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

class TaxQueryArg implements QueryArg
{

    private array $query = [];

    public function __construct(array $queryArray = [])
    {
        $this->query = $queryArray;
    }

    public static function make(array $queryArray = [], array $args = []): static
    {
        return new static($queryArray);
    }

    public function where(
        string $taxonomy,
        int|string|array $terms,
        string $field = 'term_id',
        string $operator = 'IN',
        bool $includeChildren = true
    ): self {
        return $this->addClause(
            [
                'taxonomy'         => $taxonomy,
                'terms'            => $terms,
                'field'            => $field,
                'operator'         => $operator,
                'include_children' => $includeChildren,
            ],
            'AND'
        );
    }

    public function orWhere(
        string $taxonomy,
        int|string|array $terms,
        string $field = 'term_id',
        string $operator = 'IN',
        bool $includeChildren = true
    ): self {
        return $this->addClause(
            [
                'taxonomy'         => $taxonomy,
                'terms'            => $terms,
                'field'            => $field,
                'operator'         => $operator,
                'include_children' => $includeChildren,
            ],
            'OR'
        );
    }

    public function whereIn(
        string $taxonomy,
        int|string|array $terms,
        string $field = 'term_id',
        bool $includeChildren = true
    ): self {
        return $this->where(
            $taxonomy,
            $terms,
            $field,
            'IN',
            $includeChildren
        );
    }

    public function whereNotIn(
        string $taxonomy,
        int|string|array $terms,
        string $field = 'term_id',
        bool $includeChildren = true
    ): self {
        return $this->where(
            $taxonomy,
            $terms,
            $field,
            'NOT IN',
            $includeChildren
        );
    }

    public function whereAnd(
        string $taxonomy,
        int|string|array $terms,
        string $field = 'term_id'
    ): self {
        return $this->where(
            $taxonomy,
            $terms,
            $field,
            'AND',
            false
        );
    }

    public function whereExists(string $taxonomy): self
    {
        return $this->addClause(
            [
                'taxonomy' => $taxonomy,
                'operator' => 'EXISTS',
            ],
            'AND'
        );
    }

    public function whereNotExists(string $taxonomy): self
    {
        return $this->addClause(
            [
                'taxonomy' => $taxonomy,
                'operator' => 'NOT EXISTS',
            ],
            'AND'
        );
    }

    public function whereGroup(
        callable $callback,
        string $relation = 'AND'
    ): self {
        return $this->addGroup($callback, $relation, 'AND');
    }

    public function orWhereGroup(
        callable $callback,
        string $relation = 'AND'
    ): self {
        return $this->addGroup($callback, $relation, 'OR');
    }

    public function whereAny(callable $callback): self
    {
        return $this->whereGroup($callback, 'OR');
    }

    public function whereAll(callable $callback): self
    {
        return $this->whereGroup($callback, 'AND');
    }

    public function whereClause(
        array $clause,
        string $boolean = 'AND'
    ): self {
        return $this->addClause($clause, $boolean);
    }

    private function addClause(array $clause, string $boolean): self
    {
        $boolean = 'OR' === strtoupper($boolean) ? 'OR' : 'AND';

        if ([] === $this->query) {
            $this->query = [$clause];

            return $this;
        }

        if ('OR' === $boolean) {
            if (isset($this->query['relation']) && 'OR' === $this->query['relation']) {
                $this->query[] = $clause;
            } else {
                $this->query = [
                    'relation' => 'OR',
                    $this->query,
                    $clause,
                ];
            }

            return $this;
        }

        if (!isset($this->query['relation']) || 'AND' === $this->query['relation']) {
            $this->query[] = $clause;
        } else {
            $this->query = [
                'relation' => 'AND',
                $this->query,
                $clause,
            ];
        }

        return $this;
    }

    private function addGroup(
        callable $callback,
        string $relation,
        string $boolean
    ): self {
        $group = new static();

        $callback($group);

        $query = $group->toArray();

        if ([] === $query) {
            return $this;
        }

        $relation = 'OR' === strtoupper($relation) ? 'OR' : 'AND';

        if (isset($query['relation'])) {
            $query['relation'] = $relation;
        } else {
            $query = [
                'relation' => $relation,
                $query,
            ];
        }

        return $this->addClause($query, $boolean);
    }

    public function toArray(): array
    {
        return $this->query;
    }
}
