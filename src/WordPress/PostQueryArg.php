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

class PostQueryArg implements QueryArg
{
    use SupportsQueryArguments;

    private IncludeExcludeQueryArg $postIdQuery;
    private IncludeExcludeQueryArg $authorQuery;
    private IncludeExcludeQueryArg $parentQuery;
    private IncludeExcludeIsQueryArg $postTypeQuery;
    private IncludeExcludeIsQueryArg $postStatusQuery;
    private SearchQueryArg $searchQuery;
    private DateQueryArg $dateQuery;
    private MetaQueryArg $metaQuery;
    private TaxQueryArg $taxQuery;

    private ?int $postsPerPage = null;
    private ?int $page = null;
    private ?int $offset = null;
    private string|array|null $orderBy = null;
    private ?string $order = null;
    private ?string $postName = null;
    private array $postNameIn = [];
    private ?int $menuOrder = null;
    private ?bool $hasPassword = null;
    private ?string $postPassword = null;
    private ?bool $ignoreStickyPosts = null;
    private ?bool $noFoundRows = null;
    private ?bool $suppressFilters = null;
    private ?bool $cacheResults = null;
    private ?bool $updatePostMetaCache = null;
    private ?bool $updatePostTermCache = null;
    private ?bool $lazyLoadTermMeta = null;
    private ?string $perm = null;
    private int|array|null $commentCount = null;
    private ?bool $exact = null;
    private ?bool $sentence = null;
    private ?string $fields = null;

    public function __construct(array $queryArray = [])
    {
        $this->initialiseQueryArguments($queryArray);
        $this->postIdQuery = IncludeExcludeQueryArg::make([
            'post__in' => $queryArray['post__in'] ?? [],
            'post__not_in' => $queryArray['post__not_in'] ?? [],
        ], ['includeKey' => 'post__in', 'excludeKey' => 'post__not_in']);
        $this->authorQuery = IncludeExcludeQueryArg::make([
            'author__in' => $queryArray['author__in'] ?? [],
            'author__not_in' => $queryArray['author__not_in'] ?? [],
        ], ['includeKey' => 'author__in', 'excludeKey' => 'author__not_in']);
        $this->parentQuery = IncludeExcludeQueryArg::make([
            'post_parent__in' => $queryArray['post_parent__in'] ?? [],
            'post_parent__not_in' => $queryArray['post_parent__not_in'] ?? [],
        ], ['includeKey' => 'post_parent__in', 'excludeKey' => 'post_parent__not_in']);
        $this->postTypeQuery = IncludeExcludeIsQueryArg::make([
            'is' => $queryArray['post_type'] ?? null,
        ], ['isIdentifier' => false, 'multiValue' => true, 'includeKey' => 'post_type__in', 'isKey' => 'post_type']);
        $this->postStatusQuery = IncludeExcludeIsQueryArg::make([
            'is' => $queryArray['post_status'] ?? null,
        ], ['isIdentifier' => false, 'multiValue' => true, 'isKey' => 'post_status']);
        $this->searchQuery = SearchQueryArg::make([
            'search' => $queryArray['s'] ?? '',
        ], ['allowedSearchColumns' => ['post_title', 'post_content', 'post_excerpt']]);
        $this->dateQuery = DateQueryArg::make($queryArray['date_query'] ?? []);
        $this->metaQuery = MetaQueryArg::make($queryArray['meta_query'] ?? []);
        $this->taxQuery = TaxQueryArg::make($queryArray['tax_query'] ?? []);

        if (isset($queryArray['posts_per_page'])) {

            $this->postsPerPage = (int) $queryArray['posts_per_page'];

        }
        if (isset($queryArray['paged'])) {
            $this->page = max(1, (int) $queryArray['paged']);
        }
        if (isset($queryArray['offset'])) {
            $this->offset = max(0, (int) $queryArray['offset']);
        }
        if (isset($queryArray['orderby'])) {
            $this->orderBy = $queryArray['orderby'];
        }
        if (isset($queryArray['order'])) {
            $this->order = 'ASC' === strtoupper((string) $queryArray['order']) ? 'ASC' : 'DESC';
        }
        if (isset($queryArray['name'])) {
            $this->postName = (string) $queryArray['name'];
        }
        if (isset($queryArray['post_name__in'])) {
            $this->postNameIn = array_values(array_unique(array_map('strval', (array) $queryArray['post_name__in'])));
        }
        if (isset($queryArray['menu_order'])) {
            $this->menuOrder = (int) $queryArray['menu_order'];
        }
        if (isset($queryArray['has_password'])) {
            $this->hasPassword = (bool) $queryArray['has_password'];
        }
        if (isset($queryArray['post_password'])) {
            $this->postPassword = (string) $queryArray['post_password'];
        }
        if (isset($queryArray['ignore_sticky_posts'])) {
            $this->ignoreStickyPosts = (bool) $queryArray['ignore_sticky_posts'];
        }
        if (isset($queryArray['no_found_rows'])) {
            $this->noFoundRows = (bool) $queryArray['no_found_rows'];
        }
        if (isset($queryArray['suppress_filters'])) {
            $this->suppressFilters = (bool) $queryArray['suppress_filters'];
        }
        if (isset($queryArray['cache_results'])) {
            $this->cacheResults = (bool) $queryArray['cache_results'];
        }
        if (isset($queryArray['update_post_meta_cache'])) {
            $this->updatePostMetaCache = (bool) $queryArray['update_post_meta_cache'];
        }
        if (isset($queryArray['update_post_term_cache'])) {
            $this->updatePostTermCache = (bool) $queryArray['update_post_term_cache'];
        }
        if (isset($queryArray['lazy_load_term_meta'])) {
            $this->lazyLoadTermMeta = (bool) $queryArray['lazy_load_term_meta'];
        }
        if (isset($queryArray['perm'])) {
            $this->perm = (string) $queryArray['perm'];
        }
        if (isset($queryArray['comment_count'])) {
            $this->commentCount = $queryArray['comment_count'];
        }
        if (isset($queryArray['exact'])) {
            $this->exact = (bool) $queryArray['exact'];
        }
        if (isset($queryArray['sentence'])) {
            $this->sentence = (bool) $queryArray['sentence'];
        }
        if (isset($queryArray['fields'])) {
            $this->fields = (string) $queryArray['fields'];
        }
    }

    public static function make(array $queryArray = [], array $args = []): static
    {
        return new static($queryArray);
    }

    public function includeIds(int|float|string|array $ids): self { $this->postIdQuery->include($ids);

        return $this; }
    public function excludeIds(int|float|string|array $ids): self { $this->postIdQuery->exclude($ids);

        return $this; }
    public function postType(string|array $types): self { $this->postTypeQuery->is($types);

        return $this; }
    public function postTypesIn(string|array $types): self { $this->postTypeQuery->include($types);

        return $this; }
    public function postStatus(string|array $statuses): self { $this->postStatusQuery->is($statuses);

        return $this; }
    public function postStatusesIn(string|array $statuses): self { $this->postStatusQuery->include($statuses);

        return $this; }
    public function postStatusesNotIn(string|array $statuses): self { $this->postStatusQuery->exclude($statuses);

        return $this; }
    public function authorsIn(int|float|string|array $ids): self { $this->authorQuery->include($ids);

        return $this; }
    public function authorsNotIn(int|float|string|array $ids): self { $this->authorQuery->exclude($ids);

        return $this; }
    public function parentsIn(int|float|string|array $ids): self { $this->parentQuery->include($ids);

        return $this; }
    public function parentsNotIn(int|float|string|array $ids): self { $this->parentQuery->exclude($ids);

        return $this; }
    public function search(string $value): self { $this->searchQuery->search($value);

        return $this; }
    public function dateQuery(DateQueryArg|array $query): self { $this->dateQuery = $query instanceof DateQueryArg ? $query : DateQueryArg::make($query);

        return $this; }
    public function metaQuery(MetaQueryArg|array $query): self { $this->metaQuery = $query instanceof MetaQueryArg ? $query : MetaQueryArg::make($query);

        return $this; }
    public function taxQuery(TaxQueryArg|array $query): self { $this->taxQuery = $query instanceof TaxQueryArg ? $query : TaxQueryArg::make($query);

        return $this; }
    public function postsPerPage(int $number): self { $this->postsPerPage = $number;

        return $this; }
    public function page(int $page): self { $this->page = max(1, $page);

        return $this; }
    public function offset(int $offset): self { $this->offset = max(0, $offset);

        return $this; }
    public function orderBy(string|array $column): self { $this->orderBy = $column;

        return $this; }
    public function order(string $order): self { $this->order = 'ASC' === strtoupper($order) ? 'ASC' : 'DESC';

        return $this; }
    public function fields(string $fields): self { $this->fields = $fields;

        return $this; }

    public function toArray(): array
    {
        $args = [];
        $args += $this->postIdQuery->toArray();
        $args += $this->authorQuery->toArray();
        $args += $this->parentQuery->toArray();
        $args += $this->postTypeQuery->toArray();
        $args += $this->postStatusQuery->toArray();

        $search = $this->searchQuery->toArray();
        if (isset($search['search'])) {
            $args['s'] = $search['search'];
        }
        $date = $this->dateQuery->toArray();
        if ([] !== $date) {
            $args['date_query'] = $date;
        }
        $meta = $this->metaQuery->toArray();
        if ([] !== $meta) {
            $args['meta_query'] = $meta;
        }
        $tax = $this->taxQuery->toArray();
        if ([] !== $tax) {
            $args['tax_query'] = $tax;
        }
        $values = [
            'posts_per_page' => $this->postsPerPage,
            'paged' => $this->page,
            'offset' => $this->offset,
            'orderby' => $this->orderBy,
            'order' => $this->order,
            'name' => $this->postName,
            'menu_order' => $this->menuOrder,
            'has_password' => $this->hasPassword,
            'post_password' => $this->postPassword,
            'ignore_sticky_posts' => $this->ignoreStickyPosts,
            'no_found_rows' => $this->noFoundRows,
            'suppress_filters' => $this->suppressFilters,
            'cache_results' => $this->cacheResults,
            'update_post_meta_cache' => $this->updatePostMetaCache,
            'update_post_term_cache' => $this->updatePostTermCache,
            'lazy_load_term_meta' => $this->lazyLoadTermMeta,
            'perm' => $this->perm,
            'comment_count' => $this->commentCount,
            'exact' => $this->exact,
            'sentence' => $this->sentence,
            'fields' => $this->fields,
        ];
        foreach ($values as $key => $value) {
            if (null !== $value) {
                $args[$key] = $value;
            }
        }
        if ([] !== $this->postNameIn) {
            $args['post_name__in'] = $this->postNameIn;
        }
        return $this->mergeQueryArguments($args);
    }
}
