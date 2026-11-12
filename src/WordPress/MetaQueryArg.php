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

class MetaQueryArg implements QueryArg
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

	public function where(string $key, $operator, $value = null, string $type = 'CHAR'): self
	{
		if (2 === \func_num_args()) {
			$value    = $operator;
			$operator = '=';
		}

		return $this->addClause(
			[
				'key'     => $key,
				'value'   => $value,
				'compare' => $operator,
				'type'    => $type,
			],
			'AND'
		);
	}

	public function orWhere(string $key, $operator, $value = null, string $type = 'CHAR'): self
	{
		if (2 === \func_num_args()) {
			$value    = $operator;
			$operator = '=';
		}

		return $this->addClause(
			[
				'key'     => $key,
				'value'   => $value,
				'compare' => $operator,
				'type'    => $type,
			],
			'OR'
		);
	}

	public function whereIn(string $key, array $values, string $type = 'CHAR'): self
	{
		return $this->where($key, 'IN', $values, $type);
	}

	public function whereNotIn(string $key, array $values, string $type = 'CHAR'): self
	{
		return $this->where($key, 'NOT IN', $values, $type);
	}

	public function whereBetween(string $key, array $values, string $type = 'CHAR'): self
	{
		return $this->where($key, 'BETWEEN', $values, $type);
	}

	public function whereNotBetween(string $key, array $values, string $type = 'CHAR'): self
	{
		return $this->where($key, 'NOT BETWEEN', $values, $type);
	}

	public function whereLike(string $key, string $value): self
	{
		return $this->where($key, 'LIKE', $value);
	}

	public function whereNotLike(string $key, string $value): self
	{
		return $this->where($key, 'NOT LIKE', $value);
	}

	public function whereRegexp(string $key, string $pattern): self
	{
		return $this->where($key, 'REGEXP', $pattern);
	}

	public function whereNotRegexp(string $key, string $pattern): self
	{
		return $this->where($key, 'NOT REGEXP', $pattern);
	}

	public function whereExists(string $key): self
	{
		return $this->addClause(
			[
				'key'     => $key,
				'compare' => 'EXISTS',
			],
			'AND'
		);
	}

	public function whereNotExists(string $key): self
	{
		return $this->addClause(
			[
				'key'     => $key,
				'compare' => 'NOT EXISTS',
			],
			'AND'
		);
	}

	public function whereMetaKey(string $key, string $comparison = '=', string $type = ''): self
	{
		$clause = [
			'key'         => $key,
			'compare'     => 'EXISTS',
			'compare_key' => $comparison,
		];

		if ('' !== $type) {
			$clause['type_key'] = $type;
		}

		return $this->addClause($clause, 'AND');
	}

	public function whereGroup(callable $callback, string $relation = 'AND'): self
	{
		return $this->addGroup($callback, $relation, 'AND');
	}

	public function orWhereGroup(callable $callback, string $relation = 'AND'): self
	{
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

	public function whereClause(array $clause, string $boolean = 'AND'): self
	{
		return $this->addClause($clause, $boolean);
	}

	private function addClause(array $clause, string $boolean): self
	{
		$boolean = 'OR' === strtoupper($boolean) ? 'OR' : 'AND';

		if (empty($this->query)) {
			$this->query = [$clause];
			return $this;
		}

		if ($this->isFirstOrderClause($this->query)) {
			$this->query = [$this->query];
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

	private function addGroup(callable $callback, string $relation, string $boolean): self
	{
		$nested = new self();
		$callback($nested);

		$conditions = $nested->toArray();

		if (empty($conditions)) {
			return $this;
		}

		$relation = 'OR' === strtoupper($relation) ? 'OR' : 'AND';

		if (isset($conditions['relation'])) {
			$conditions['relation'] = $relation;
		} else {
			$conditions = [
				'relation' => $relation,
				$conditions,
			];
		}

		return $this->addClause($conditions, $boolean);
	}

    private function isFirstOrderClause(array $query): bool
    {
        return isset($query['key']) || isset($query['value']);
    }

	public function toArray(): array
	{
		return $this->query;
	}
}
