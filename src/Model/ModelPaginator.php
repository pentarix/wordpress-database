<?php

/**
 * @author    Sina Kuhestani <sinakuhestani@gmail.com>
 * @copyright 2026 Sina Kuhestani (EphpicMan)
 * @link      https://github.com/ephpicman GitHub
 * @link      https://www.linkedin.com/in/sinakuhestani LinkedIn
 * @license   See the LICENSE file in the project root.
 */

declare(strict_types=1);

namespace Pentarix\\WordPressDatabase\\Model;

/**
 * Immutable-by-convention pagination result for model queries.
 */
final class ModelPaginator
{
    /** @var array<int, Model> */
    public array $items;

    public int $total;

    public int $perPage;

    public int $currentPage;

    public int $lastPage;

    /**
     * @param array<int, Model> $items
     */
    public function __construct(
        array $items,
        int $total,
        int $perPage,
        int $currentPage,
        int $lastPage
    ) {
        $this->items = $items;
        $this->total = $total;
        $this->perPage = $perPage;
        $this->currentPage = $currentPage;
        $this->lastPage = $lastPage;
    }

    /** @return array<int, Model> */
    public function getItems(): array
    {
        return $this->items;
    }
}
