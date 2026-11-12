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

final class UserQuery
{
    private array $queryArguments;

    public function __construct(UserQueryArg|array $queryArguments = [])
    {
        $this->queryArguments = $queryArguments instanceof UserQueryArg
            ? $queryArguments->toArray()
            : $queryArguments;
    }

    public static function make(UserQueryArg|array $queryArguments = []): self
    {
        return new self($queryArguments);
    }

    /**
     * Execute the query and return the native WordPress query object.
     */
    public function execute(): \WP_User_Query
    {
        $this->ensureWordPressIsLoaded();

        // WP_User_Query skips execution when its constructor receives an empty array.
        $queryArguments = $this->queryArguments ?: ['fields' => 'all'];

        return new \WP_User_Query($queryArguments);
    }

    /**
     * Retrieve the results matching the configured query.
     *
     * @return array<int, mixed>
     */
    public function get(): array
    {
        $results = $this->execute()->get_results();

        return \is_array($results) ? $results : [];
    }

    /**
     * Retrieve the first result from the configured query, or null when empty.
     *
     * The return value follows the configured "fields" query argument.
     */
    public function first(): mixed
    {
        $this->ensureWordPressIsLoaded();

        $queryArguments = $this->queryArguments;
        $queryArguments['number'] = 1;

        $query = new \WP_User_Query($queryArguments ?: ['number' => 1]);

        return $query->get_results()[0] ?? null;
    }

    /**
     * Retrieve the total number of users matching the configured query.
     */
    public function count(): int
    {
        $this->ensureWordPressIsLoaded();

        $queryArguments = $this->queryArguments;
        $queryArguments['number'] = 1;
        $queryArguments['count_total'] = true;

        return (int) (new \WP_User_Query($queryArguments))->get_total();
    }

    /**
     * Execute a paginated query and return WordPress pagination results.
     *
     * The returned WP_User_Query exposes the results and total user count.
     * An explicit offset is ignored so that the requested page determines the
     * result offset.
     */
    public function paginate(int $perPage, int $page = 1): \WP_User_Query
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
        $queryArguments['count_total'] = true;

        unset($queryArguments['offset']);

        return new \WP_User_Query($queryArguments);
    }

    private function ensureWordPressIsLoaded(): void
    {
        if (!class_exists(\WP_User_Query::class)) {
            throw new LogicException('UserQuery requires WordPress to be loaded.');
        }
    }
}
