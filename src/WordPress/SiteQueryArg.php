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

class SiteQueryArg implements QueryArg
{
    use SupportsQueryArguments;

    private IncludeExcludeQueryArg $siteIds;
    private IncludeExcludeQueryArg $networkIds;
    private ?int $siteId = null;
    private ?int $networkId = null;
    private ?string $domain = null;
    private ?string $path = null;
    private ?string $search = null;
    private ?int $number = null;
    private ?int $offset = null;
    private string|array|null $orderby = null;
    private ?string $order = null;
    private ?string $fields = null;
    private ?int $public = null;
    private ?int $archived = null;
    private ?int $mature = null;
    private ?int $spam = null;
    private ?int $deleted = null;
    private ?int $langId = null;
    private ?bool $updateSiteCache = null;

    public function __construct(array $queryArray = [])
    {
        $this->initialiseQueryArguments($queryArray);
        $this->siteIds = IncludeExcludeQueryArg::make(['site__in' => $queryArray['site__in'] ?? [], 'site__not_in' => $queryArray['site__not_in'] ?? []], ['includeKey' => 'site__in', 'excludeKey' => 'site__not_in']);
        $this->networkIds = IncludeExcludeQueryArg::make(['network__in' => $queryArray['network__in'] ?? [], 'network__not_in' => $queryArray['network__not_in'] ?? []], ['includeKey' => 'network__in', 'excludeKey' => 'network__not_in']);
        if (array_key_exists('ID', $queryArray)) {
            $this->siteId = (int) $queryArray['ID'];
        }
        if (array_key_exists('network_id', $queryArray)) {
            $this->networkId = (int) $queryArray['network_id'];
        }
        if (array_key_exists('domain', $queryArray)) {
            $this->domain = (string) $queryArray['domain'];
        }
        if (array_key_exists('path', $queryArray)) {
            $this->path = (string) $queryArray['path'];
        }
        if (array_key_exists('search', $queryArray)) {
            $this->search = (string) $queryArray['search'];
        }
        if (array_key_exists('number', $queryArray)) {
            $this->number = (int) $queryArray['number'];
        }
        if (array_key_exists('offset', $queryArray)) {
            $this->offset = (int) $queryArray['offset'];
        }
        if (array_key_exists('orderby', $queryArray)) {
            $this->orderby = $queryArray['orderby'];
        }
        if (array_key_exists('order', $queryArray)) {
            $this->order = (string) $queryArray['order'];
        }
        if (array_key_exists('fields', $queryArray)) {
            $this->fields = (string) $queryArray['fields'];
        }
        if (array_key_exists('public', $queryArray)) {
            $this->public = null === $queryArray['public'] ? null : (int) $queryArray['public'];
        }
        if (array_key_exists('archived', $queryArray)) {
            $this->archived = null === $queryArray['archived'] ? null : (int) $queryArray['archived'];
        }
        if (array_key_exists('mature', $queryArray)) {
            $this->mature = null === $queryArray['mature'] ? null : (int) $queryArray['mature'];
        }
        if (array_key_exists('spam', $queryArray)) {
            $this->spam = null === $queryArray['spam'] ? null : (int) $queryArray['spam'];
        }
        if (array_key_exists('deleted', $queryArray)) {
            $this->deleted = null === $queryArray['deleted'] ? null : (int) $queryArray['deleted'];
        }
        if (array_key_exists('lang_id', $queryArray)) {
            $this->langId = (int) $queryArray['lang_id'];
        }
        if (array_key_exists('update_site_cache', $queryArray)) {
            $this->updateSiteCache = (bool) $queryArray['update_site_cache'];
        }
    }

    public static function make(array $queryArray = [], array $args = []): static
    {
        return new static($queryArray);
    }

    public function includeSites(int|array $ids): self
    {
        $this->siteIds->include($ids);

        return $this;
    }

    public function excludeSites(int|array $ids): self
    {
        $this->siteIds->exclude($ids);

        return $this;
    }

    public function networkId(int $id): self
    {
        $this->networkId = $id;

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
        $args += $this->siteIds->toArray();
        $args += $this->networkIds->toArray();
        $values = [
            'ID' => $this->siteId,
            'network_id' => $this->networkId,
            'domain' => $this->domain,
            'path' => $this->path,
            'search' => $this->search,
            'number' => $this->number,
            'offset' => $this->offset,
            'orderby' => $this->orderby,
            'order' => $this->order,
            'fields' => $this->fields,
            'public' => $this->public,
            'archived' => $this->archived,
            'mature' => $this->mature,
            'spam' => $this->spam,
            'deleted' => $this->deleted,
            'lang_id' => $this->langId,
            'update_site_cache' => $this->updateSiteCache,
        ];
        foreach ($values as $key => $value) {
            if (null !== $value) {
                $args[$key] = $value;
            }
        }

        return $this->mergeQueryArguments($args);
    }
}
