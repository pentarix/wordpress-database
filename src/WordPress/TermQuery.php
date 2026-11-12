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

final class TermQuery
{
    private array $queryArguments;

    public function __construct(TermQueryArg|array $queryArguments = [])
    {
        $this->queryArguments = $queryArguments instanceof TermQueryArg
            ? $queryArguments->toArray()
            : $queryArguments;
    }

    public static function make(TermQueryArg|array $queryArguments = []): self
    {
        return new self($queryArguments);
    }

    /**
     * Execute the query and return the native WordPress query object.
     */
    public function execute(): \WP_Term_Query
    {
        $this->ensureWordPressIsLoaded();

        // WP_Term_Query skips execution when its constructor receives an empty array.
        $queryArguments = $this->queryArguments ?: ['fields' => 'all'];

        return new \WP_Term_Query($queryArguments);
    }

    /**
     * Retrieve the terms matching the configured query.
     *
     * The return value follows the native "fields" query argument.
     *
     * @return array<int|string, mixed>|int|string
     */
    public function get(): array|int|string
    {
        $this->ensureWordPressIsLoaded();

        $queryArguments = $this->queryArguments ?: ['fields' => 'all'];
        $query = new \\WP_Term_Query();

        $terms = $query->query($queryArguments);

        return is_array($terms) || is_int($terms) || is_string($terms) ? $terms : [];
    }

    /**
     * Retrieve the first result matching the configured query, or null.
     *
     * The return value follows the configured "fields" query argument.
     */
    public function first(): mixed
    {
        $this->ensureWordPressIsLoaded();

        $queryArguments = $this->queryArguments;
        $queryArguments['number'] = 1;

        $query = new \WP_Term_Query($queryArguments ?: ['number' => 1]);

        if (!is_array($query->terms) || [] === $query->terms) {
            return null;
        }

        return reset($query->terms);
    }

    /**
     * Retrieve the total number of terms matching the configured query.
     */
    public function count(): int
    {
        $this->ensureWordPressIsLoaded();

        $queryArguments = $this->queryArguments;
        $queryArguments['fields'] = 'count';

        $query = new \WP_Term_Query($queryArguments ?: ['fields' => 'count']);

        return (int) $query->terms;
    }

    /**
     * Execute a limited term query using WordPress's native number and offset arguments.
     *
     * The requested page is translated into an offset. WordPress may intentionally
     * omit SQL limits for hierarchical term queries, so callers must not assume
     * that every taxonomy honours the requested page size.
     */
    public function paginate(int $perPage, int $page = 1): \WP_Term_Query
    {
        if ($perPage < 1) {
            throw new \InvalidArgumentException('The per-page value must be greater than zero.');
        }

        if ($page < 1) {
            throw new \InvalidArgumentException('The page number must be greater than zero.');
        }

        $this->ensureWordPressIsLoaded();

        $queryArguments = $this->queryArguments;
        $existingOffset = abs((int) ($queryArguments['offset'] ?? 0));
        $queryArguments['number'] = $perPage;
        $queryArguments['offset'] = $existingOffset + (($page - 1) * $perPage);

        return new \WP_Term_Query($queryArguments);
    }

    private function ensureWordPressIsLoaded(): void
    {
        if (!class_exists(\WP_Term_Query::class)) {
            throw new LogicException('TermQuery requires WordPress to be loaded.');
        }
    }
}
