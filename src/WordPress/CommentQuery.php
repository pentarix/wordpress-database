<?php

/**
 * @author    Sina Kuhestani <sinakuhestani@gmail.com>
 * @copyright 2026 Sina Kuhestani (EphpicMan)
 * @link      https://github.com/ephpicman GitHub
 * @link      https://www.linkedin.com/in/sinakuhestani LinkedIn
 * @license   See the LICENSE file in the project root.
 */

declare(strict_types=1);

namespace Pentarix\\WordPressDatabase\\WordPress;

use InvalidArgumentException;
use LogicException;

final class CommentQuery
{
    private array $queryArguments;

    public function __construct(CommentQueryArg|array $queryArguments = [])
    {
        $this->queryArguments = $queryArguments instanceof CommentQueryArg
            ? $queryArguments->toArray()
            : $queryArguments;
    }

    public static function make(CommentQueryArg|array $queryArguments = []): self
    {
        return new self($queryArguments);
    }

    /**
     * Execute the query and return the native WordPress query object.
     */
    public function execute(): \\WP_Comment_Query
    {
        $this->ensureWordPressIsLoaded();

        // WP_Comment_Query skips execution when its constructor receives an empty array.
        $queryArguments = $this->queryArguments ?: ['number' => 0];

        return new \\WP_Comment_Query($queryArguments);
    }

    /**
     * Retrieve the comments matching the configured query.
     *
     * The return value follows the native "fields" and "count" query arguments.
     *
     * @return array<int, mixed>|int
     */
    public function get(): array|int
    {
        $comments = $this->execute()->comments;

        return is_array($comments) || is_int($comments) ? $comments : [];
    }

    /**
     * Retrieve the first comment matching the configured query, or null.
     *
     * The return value follows the configured "fields" query argument.
     */
    public function first(): mixed
    {
        $this->ensureWordPressIsLoaded();

        $queryArguments = $this->queryArguments;
        $queryArguments['number'] = 1;
        $queryArguments['paged'] = 1;
        $queryArguments['count'] = false;

        $query = new \\WP_Comment_Query($queryArguments ?: ['number' => 1]);

        return $query->comments[0] ?? null;
    }

    /**
     * Retrieve the total number of comments matching the configured query.
     */
    public function count(): int
    {
        $this->ensureWordPressIsLoaded();

        $queryArguments = $this->queryArguments;
        $queryArguments['number'] = 1;
        $queryArguments['offset'] = 0;
        $queryArguments['paged'] = 1;
        $queryArguments['count'] = false;
        $queryArguments['no_found_rows'] = false;

        $query = new \\WP_Comment_Query($queryArguments);

        return (int) $query->found_comments;
    }

    /**
     * Execute a paginated query and return WordPress pagination metadata.
     *
     * The returned WP_Comment_Query exposes comments, found_comments, and
     * max_num_pages. An explicit offset is removed so the requested page
     * determines the result offset.
     */
    public function paginate(int $perPage, int $page = 1): \\WP_Comment_Query
    {
        if ($perPage < 1) {
            throw new InvalidArgumentException('The per-page value must be greater than zero.');
        }

        if ($page < 1) {
            throw new InvalidArgumentException('The page number must be greater than zero.');
        }

        $this->ensureWordPressIsLoaded();

        $queryArguments = $this->queryArguments;
        $queryArguments['number'] = $perPage;
        $queryArguments['paged'] = $page;
        $queryArguments['count'] = false;
        $queryArguments['no_found_rows'] = false;

        unset($queryArguments['offset']);

        return new \\WP_Comment_Query($queryArguments);
    }

    private function ensureWordPressIsLoaded(): void
    {
        if (!class_exists(\\WP_Comment_Query::class)) {
            throw new LogicException('CommentQuery requires WordPress to be loaded.');
        }
    }
}
