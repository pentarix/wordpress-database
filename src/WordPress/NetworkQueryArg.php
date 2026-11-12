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

class NetworkQueryArg implements QueryArg
{
    use SupportsQueryArguments;

    private IncludeExcludeQueryArg $networkIds;
    private ?string $domain = null;
    private ?string $path = null;
    private ?int $number = null;
    private ?int $offset = null;
    private string|array|null $orderby = null;
    private ?string $order = null;
    private ?string $fields = null;
    private ?bool $count = null;
    private ?bool $updateNetworkCache = null;

    public function __construct(array $queryArray = [])
    {
        $this->initialiseQueryArguments($queryArray);
        $this->networkIds = IncludeExcludeQueryArg::make(['network__in' => $queryArray['network__in'] ?? [], 'network__not_in' => $queryArray['network__not_in'] ?? []], ['includeKey' => 'network__in', 'excludeKey' => 'network__not_in']);
        if (array_key_exists('domain', $queryArray)) {
            $this->domain = (string) $queryArray['domain'];
        }
        if (array_key_exists('path', $queryArray)) {
            $this->path = (string) $queryArray['path'];
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
        if (array_key_exists('count', $queryArray)) {
            $this->count = (bool) $queryArray['count'];
        }
        if (array_key_exists('update_network_cache', $queryArray)) {
            $this->updateNetworkCache = (bool) $queryArray['update_network_cache'];
        }
    }

    public static function make(array $queryArray = [], array $args = []): static
    {
        return new static($queryArray);
    }

    public function includeNetworks(int|array $ids): self
    {
        $this->networkIds->include($ids);

        return $this;
    }

    public function excludeNetworks(int|array $ids): self
    {
        $this->networkIds->exclude($ids);

        return $this;
    }

    public function toArray(): array
    {
        $args = [];
        $args += $this->networkIds->toArray();
        $values = [
            'domain' => $this->domain,
            'path' => $this->path,
            'number' => $this->number,
            'offset' => $this->offset,
            'orderby' => $this->orderby,
            'order' => $this->order,
            'fields' => $this->fields,
            'count' => $this->count,
            'update_network_cache' => $this->updateNetworkCache,
        ];
        foreach ($values as $key => $value) {
            if (null !== $value) {
                $args[$key] = $value;
            }
        }

        return $this->mergeQueryArguments($args);
    }
}
