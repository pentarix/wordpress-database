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

use LogicException;

final class PostQuery
{
    private array $queryArguments;

    public function __construct(PostQueryArg|array $queryArguments = [])
    {
        $this->queryArguments = $queryArguments instanceof PostQueryArg
            ? $queryArguments->toArray()
            : $queryArguments;
    }

    public static function make(PostQueryArg|array $queryArguments = []): self
    {
        return new self($queryArguments);
    }

    /**
     * Execute the query and return the native WordPress query object.
     */
    public function execute(): \\WP_Query
    {
        $this->ensureWordPressIsLoaded();

        return new \\WP_Query($this->queryArguments);
    }

    /**
     * Retrieve the posts matching the configured query.
     *
     * @return array<int, mixed>
     */
    public function get(): array
    {
        return $this->execute()->posts;
    }

    /**
     * Retrieve the first matching post, or null when no post is found.
     *
     * The return value follows the configured "fields" query argument.
     */
    public function first(): mixed
    {
        $this->ensureWordPressIsLoaded();

        $queryArguments = $this->queryArguments;
        $queryArguments['posts_per_page'] = 1;

        $query = new \\WP_Query($queryArguments);

        return $query->posts[0] ?? null;
    }

    /**
     * Retrieve the total number of matching posts, ignoring the page size.
     */
    public function count(): int
    {
        $this->ensureWordPressIsLoaded();

        $queryArguments = $this->queryArguments;
        $queryArguments['posts_per_page'] = 1;
        $queryArguments['no_found_rows'] = false;

        return (int) (new \\WP_Query($queryArguments))->found_posts;
    }

    private function ensureWordPressIsLoaded(): void
    {
        if (!class_exists(\\WP_Query::class)) {
            throw new LogicException('PostQuery requires WordPress to be loaded.');
        }
    }
}
