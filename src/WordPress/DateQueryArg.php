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

class DateQueryArg implements QueryArg
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

    public function where(array $clause): self
    {
        return $this->addClause($clause, 'AND');
    }

    public function orWhere(array $clause): self
    {
        return $this->addClause($clause, 'OR');
    }

    public function after(
        string|array $date,
        bool $inclusive = false,
        ?string $column = null
    ): self {
        $clause = [
            'after' => $date,
            'inclusive' => $inclusive,
        ];

        if (null !== $column) {
            $clause['column'] = $column;
        }

        return $this->where($clause);
    }

    public function before(
        string|array $date,
        bool $inclusive = false,
        ?string $column = null
    ): self {
        $clause = [
            'before' => $date,
            'inclusive' => $inclusive,
        ];

        if (null !== $column) {
            $clause['column'] = $column;
        }

        return $this->where($clause);
    }

    public function between(
        string|array $after,
        string|array $before,
        bool $inclusive = true,
        ?string $column = null
    ): self {
        $clause = [
            'after' => $after,
            'before' => $before,
            'inclusive' => $inclusive,
        ];

        if (null !== $column) {
            $clause['column'] = $column;
        }

        return $this->where($clause);
    }

    public function year(int|array $year, ?string $column = null, string $compare = '='): self
    {
        return $this->datePart('year', $year, $column, $compare);
    }

    public function month(int|array $month, ?string $column = null, string $compare = '='): self
    {
        return $this->datePart('month', $month, $column, $compare);
    }

    public function week(int|array $week, ?string $column = null, string $compare = '='): self
    {
        return $this->datePart('week', $week, $column, $compare);
    }

    public function day(int|array $day, ?string $column = null, string $compare = '='): self
    {
        return $this->datePart('day', $day, $column, $compare);
    }

    public function hour(int|array $hour, ?string $column = null, string $compare = '='): self
    {
        return $this->datePart('hour', $hour, $column, $compare);
    }

    public function minute(int|array $minute, ?string $column = null, string $compare = '='): self
    {
        return $this->datePart('minute', $minute, $column, $compare);
    }

    public function second(int|array $second, ?string $column = null, string $compare = '='): self
    {
        return $this->datePart('second', $second, $column, $compare);
    }

    public function dayOfYear(int|array $day, ?string $column = null, string $compare = '='): self
    {
        return $this->datePart('dayofyear', $day, $column, $compare);
    }

    public function dayOfWeek(int|array $day, ?string $column = null, string $compare = '='): self
    {
        return $this->datePart('dayofweek', $day, $column, $compare);
    }

    public function dayOfWeekIso(int|array $day, ?string $column = null, string $compare = '='): self
    {
        return $this->datePart('dayofweek_iso', $day, $column, $compare);
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

    private function datePart(string $key, int|array $value, ?string $column, string $compare): self
    {
        $clause = [
            $key => $value,
        ];

        if (null !== $column) {
            $clause['column'] = $column;
        }

        if ('=' !== $compare) {
            $clause['compare'] = $compare;
        }

        return $this->where($clause);
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
