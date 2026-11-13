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

/**
 * A small CRUD and prepared-query helper for application-owned tables.
 *
 * Core WordPress entities should continue to use their dedicated Core APIs.
 * This class is only for tables owned by the consuming application.
 */
final class CustomTable
{
    private const PLACEHOLDER_PATTERN = '/(?<!%)(?:%%)*%(?:[1-9][0-9]*\$)?[-+0-9]*(?: |0|\'.)?[-+0-9]*(?:\.[0-9]+)?[sdfFi]/';
    private \wpdb $database;

    private string $tableName;

    private function __construct(\wpdb $database, string $tableName)
    {
        $this->database = $database;
        $this->tableName = $tableName;
    }

    /**
     * Create a helper for a table scoped to the current site.
     *
     * The supplied name is a table suffix; WordPress's current site prefix is
     * added automatically.
     */
    public static function forSite(string $table): self
    {
        return self::make($table, false);
    }

    /**
     * Create a helper for a network-wide table.
     *
     * The supplied name is a table suffix; WordPress's base prefix is added.
     */
    public static function forNetwork(string $table): self
    {
        return self::make($table, true);
    }

    /** Retrieve the fully prefixed table name. */
    public function getTableName(): string
    {
        return $this->tableName;
    }

    /**
     * Insert a row and return its generated ID.
     *
     * @return int|false The insert ID, or false when WordPress reports failure.
     */
    public function insert(array $data, array|string|null $format = null): int|false
    {
        $result = $this->database->insert($this->tableName, $data, $format);

        return false === $result ? false : (int) $this->database->insert_id;
    }

    /**
     * Update rows matching the supplied conditions.
     *
     * A result of zero means no rows were changed; false means the query failed.
     *
     * @return int|false Number of affected rows, or false on failure.
     */
    public function update(
        array $data,
        array $where,
        array|string|null $format = null,
        array|string|null $whereFormat = null
    ): int|false {
        return $this->database->update($this->tableName, $data, $where, $format, $whereFormat);
    }

    /**
     * Delete rows matching the supplied conditions.
     *
     * A result of zero means no rows were deleted; false means the query failed.
     *
     * @return int|false Number of deleted rows, or false on failure.
     */
    public function delete(array $where, array|string|null $whereFormat = null): int|false
    {
        return $this->database->delete($this->tableName, $where, $whereFormat);
    }

    /**
     * Prepare a query using wpdb's placeholder handling.
     *
     * Use %d, %f, %s, and (on the supported WordPress version) %i for
     * identifiers. Values must never be concatenated into SQL.
     */
    public function prepare(string $query, array $args = []): string
    {
        $hasPlaceholders = 1 === preg_match(self::PLACEHOLDER_PATTERN, $query);

        if ([] === $args) {
            if ($hasPlaceholders) {
                throw new InvalidArgumentException('SQL placeholders require corresponding arguments.');
            }

            return $query;
        }

        if (!$hasPlaceholders) {
            throw new InvalidArgumentException('SQL arguments cannot be supplied without placeholders.');
        }

        $prepared = $this->database->prepare($query, $args);

        if (!is_string($prepared)) {
            throw new InvalidArgumentException('The SQL query could not be prepared. Check its placeholders and arguments.');
        }

        return $prepared;
    }

    /** Execute a prepared query and return wpdb's native result. */
    public function execute(string $query, array $args = []): int|bool
    {
        return $this->database->query($this->prepare($query, $args));
    }

    /**
     * Retrieve all rows as objects.
     *
     * @return array<int, object>|null
     */
    public function getResults(string $query, array $args = []): ?array
    {
        return $this->database->get_results($this->prepare($query, $args));
    }

    /** Retrieve one row as an object, or null when no row is found. */
    public function getRow(string $query, array $args = []): ?object
    {
        return $this->database->get_row($this->prepare($query, $args));
    }

    /** Retrieve one value from the first row. */
    public function getVar(string $query, array $args = []): mixed
    {
        return $this->database->get_var($this->prepare($query, $args));
    }

    private static function make(string $table, bool $networkWide): self
    {
        global $wpdb;

        if (!isset($wpdb) || !$wpdb instanceof \wpdb) {
            throw new LogicException('CustomTable requires the WordPress database connection.');
        }

        if ('' === $table || 1 !== preg_match('/^[A-Za-z0-9_]+$/D', $table)) {
            throw new InvalidArgumentException('The table name must be a non-empty alphanumeric suffix containing only underscores.');
        }

        $prefix = $networkWide ? $wpdb->base_prefix : $wpdb->prefix;

        return new self($wpdb, $prefix . $table);
    }
}
