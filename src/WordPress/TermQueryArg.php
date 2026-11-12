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

use InvalidArgumentException;

class TermQueryArg implements QueryArg
{
    use SupportsQueryArguments;

    private IncludeExcludeQueryArg $termIds;
    private ?int $parent = null;
    private string|array|null $taxonomy = null;
    private string|array|null $name = null;
    private string|array|null $slug = null;
    private ?string $search = null;
    private ?string $nameLike = null;
    private ?string $descriptionLike = null;
    private ?int $number = null;
    private ?int $offset = null;
    private ?string $orderby = null;
    private ?string $order = null;
    private ?string $fields = null;
    private ?bool $hideEmpty = null;
    private ?bool $hierarchical = null;
    private ?bool $padCounts = null;
    private ?string $get = null;
    private ?bool $updateTermMetaCache = null;
    private ?string $cacheDomain = null;

    public function __construct(array $queryArray = [])
    {
        $this->initialiseQueryArguments($queryArray);
        $this->termIds = IncludeExcludeQueryArg::make(['include' => $queryArray['include'] ?? [], 'exclude' => $queryArray['exclude'] ?? []], ['includeKey' => 'include', 'excludeKey' => 'exclude']);
        if (array_key_exists('parent', $queryArray)) {
            $this->parent = (int) $queryArray['parent'];
        }
        if (array_key_exists('taxonomy', $queryArray)) {
            $this->taxonomy = is_array($queryArray['taxonomy']) ? array_values(array_map('strval', $queryArray['taxonomy'])) : (string) $queryArray['taxonomy'];
        }
        if (array_key_exists('name', $queryArray)) {
            $this->name = is_array($queryArray['name']) ? array_values(array_map('strval', $queryArray['name'])) : (string) $queryArray['name'];
        }
        if (array_key_exists('slug', $queryArray)) {
            $this->slug = is_array($queryArray['slug']) ? array_values(array_map('strval', $queryArray['slug'])) : (string) $queryArray['slug'];
        }
        if (array_key_exists('search', $queryArray)) {
            $this->search = (string) $queryArray['search'];
        }
        if (array_key_exists('name__like', $queryArray)) {
            $this->nameLike = (string) $queryArray['name__like'];
        }
        if (array_key_exists('description__like', $queryArray)) {
            $this->descriptionLike = (string) $queryArray['description__like'];
        }
        if (array_key_exists('number', $queryArray)) {
            $this->number = (int) $queryArray['number'];
        }
        if (array_key_exists('offset', $queryArray)) {
            $this->offset = (int) $queryArray['offset'];
        }
        if (array_key_exists('orderby', $queryArray)) {
            if (null !== $queryArray['orderby'] && !is_string($queryArray['orderby'])) {
                throw new InvalidArgumentException('The term query orderby argument must be a string.');
            }

            $this->orderby = $queryArray['orderby'];
        }
        if (array_key_exists('order', $queryArray)) {
            $this->order = (string) $queryArray['order'];
        }
        if (array_key_exists('fields', $queryArray)) {
            $this->fields = (string) $queryArray['fields'];
        }
        if (array_key_exists('hide_empty', $queryArray)) {
            $this->hideEmpty = (bool) $queryArray['hide_empty'];
        }
        if (array_key_exists('hierarchical', $queryArray)) {
            $this->hierarchical = (bool) $queryArray['hierarchical'];
        }
        if (array_key_exists('pad_counts', $queryArray)) {
            $this->padCounts = (bool) $queryArray['pad_counts'];
        }
        if (array_key_exists('get', $queryArray)) {
            $this->get = (string) $queryArray['get'];
        }
        if (array_key_exists('update_term_meta_cache', $queryArray)) {
            $this->updateTermMetaCache = (bool) $queryArray['update_term_meta_cache'];
        }
        if (array_key_exists('cache_domain', $queryArray)) {
            $this->cacheDomain = (string) $queryArray['cache_domain'];
        }
    }

    public static function make(array $queryArray = [], array $args = []): static
    {
        return new static($queryArray);
    }

    public function includeTerms(int|array $ids): self
    {
        $this->termIds->include($ids);

        return $this;
    }

    public function excludeTerms(int|array $ids): self
    {
        $this->termIds->exclude($ids);

        return $this;
    }

    public function taxonomy(string|array $taxonomy): self
    {
        $this->taxonomy = $taxonomy;

        return $this;
    }

    public function orderBy(string $orderby): self
    {
        $this->orderby = $orderby;

        return $this;
    }

    public function search(string $value): self
    {
        $this->search = $value;

        return $this;
    }

    public function toArray(): array
    {
        $args = [];
        $args += $this->termIds->toArray();
        $values = [
            'parent' => $this->parent,
            'taxonomy' => $this->taxonomy,
            'name' => $this->name,
            'slug' => $this->slug,
            'search' => $this->search,
            'name__like' => $this->nameLike,
            'description__like' => $this->descriptionLike,
            'number' => $this->number,
            'offset' => $this->offset,
            'orderby' => $this->orderby,
            'order' => $this->order,
            'fields' => $this->fields,
            'hide_empty' => $this->hideEmpty,
            'hierarchical' => $this->hierarchical,
            'pad_counts' => $this->padCounts,
            'get' => $this->get,
            'update_term_meta_cache' => $this->updateTermMetaCache,
            'cache_domain' => $this->cacheDomain,
        ];
        foreach ($values as $key => $value) {
            if (null !== $value) {
                $args[$key] = $value;
            }
        }

        return $this->mergeQueryArguments($args);
    }
}
