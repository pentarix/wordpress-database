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

use InvalidArgumentException;
use LogicException;

final class NetworkQuery
{
    private array $queryArguments;

    public function __construct(NetworkQueryArg|array $queryArguments = [])
    {
        $this->queryArguments = $queryArguments instanceof NetworkQueryArg
            ? $queryArguments->toArray()
            : $queryArguments;
    }

    public static function make(NetworkQueryArg|array $queryArguments = []): self
    {
        return new self($queryArguments);
    }

    /**
     * Execute the query and return the native WordPress query object.
     */
    public function execute(): \WP_Network_Query
    {
        $this->ensureWordPressIsLoaded();

        // WP_Network_Query skips execution when its constructor receives an empty argument.
        $queryArguments = $this->queryArguments ?: ['number' => 0];

        return new \WP_Network_Query($queryArguments);
    }

    /**
     * Retrieve the networks matching the configured query.
     *
     * The return value follows the native count and fields query arguments.
     *
     * @return array<int, mixed>|int
     */
    public function get(): array|int
    {
        $this->ensureWordPressIsLoaded();

        $queryArguments = $this->queryArguments ?: ['number' => 0];
        $query = new \WP_Network_Query();

        $networks = $query->query($queryArguments);

        return is_array($networks) || is_int($networks) ? $networks : [];
    }

    /**
     * Retrieve the first result matching the configured query, or null.
     *
     * The return value follows the configured fields query argument.
     */
    public function first(): mixed
    {
        $this->ensureWordPressIsLoaded();

        $queryArguments = $this->queryArguments;
        $queryArguments['number'] = 1;
        $queryArguments['count'] = false;

        $query = new \WP_Network_Query();
        $networks = $query->query($queryArguments ?: ['number' => 1]);

        return is_array($networks) ? ($networks[0] ?? null) : null;
    }

    /**
     * Retrieve the total number of networks matching the configured query.
     */
    public function count(): int
    {
        $this->ensureWordPressIsLoaded();

        $queryArguments = $this->queryArguments;
        $queryArguments['count'] = true;
        $queryArguments['number'] = 0;
        $queryArguments['offset'] = 0;

        $query = new \WP_Network_Query();

        return (int) $query->query($queryArguments ?: ['count' => true, 'number' => 0]);
    }

    /**
     * Execute a paginated query and return WordPress pagination metadata.
     *
     * The returned WP_Network_Query exposes found_networks and max_num_pages.
     * An existing offset is retained as the starting offset before the page offset is added.
     */
    public function paginate(int $perPage, int $page = 1): \WP_Network_Query
    {
        if ($perPage < 1) {
            throw new InvalidArgumentException('The per-page value must be greater than zero.');
        }

        if ($page < 1) {
            throw new InvalidArgumentException('The page number must be greater than zero.');
        }

        $this->ensureWordPressIsLoaded();

        $queryArguments = $this->queryArguments;
        $existingOffset = abs((int) ($queryArguments['offset'] ?? 0));
        $queryArguments['number'] = $perPage;
        $queryArguments['offset'] = $existingOffset + (($page - 1) * $perPage);
        $queryArguments['no_found_rows'] = false;
        $queryArguments['count'] = false;

        return new \WP_Network_Query($queryArguments);
    }

    private function ensureWordPressIsLoaded(): void
    {
        if (!class_exists(\WP_Network_Query::class)) {
            throw new LogicException('NetworkQuery requires WordPress to be loaded.');
        }
    }
}
