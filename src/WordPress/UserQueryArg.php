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

class UserQueryArg implements QueryArg
{
    use SupportsQueryArguments;

    public const USER_COLUMNS = [
        'ID',
        'user_login',
        'user_pass',
        'user_nicename',
        'user_email',
        'user_url',
        'user_registered',
        'user_activation_key',
        'user_status',
        'display_name',
    ];

    private IncludeExcludeQueryArg $idQuery;

    private IncludeExcludeIsQueryArg $roleQuery;

    private IncludeExcludeIsQueryArg $capabilityQuery;

    private IncludeExcludeIsQueryArg $loginQuery;

    private IncludeExcludeIsQueryArg $nicenameQuery;

    private SearchQueryArg $searchQuery;

    private MetaQueryArg $metaQuery;

    private string|array|null $orderBy = null;

    private ?string $order = null;

    private ?int $number = null;

    private ?int $offset = null;

    private string|array|null $fields = null;

    private ?bool $countTotal = null;

    private ?int $blogId = null;

    private string|array|null $metaKey = null;

    private mixed $metaValue = null;

    private bool $hasMetaValue = false;

    private ?string $metaCompare = null;

    private ?string $metaType = null;

    private ?string $metaCompareKey = null;

    private ?string $metaTypeKey = null;

    private ?int $paged = null;

    private ?string $who = null;

    private bool|array|null $hasPublishedPosts = null;

    private ?bool $cacheResults = null;

    public function __construct(array $queryArray = [])
    {
        $this->initialiseQueryArguments($queryArray);
        $this->idQuery = IncludeExcludeQueryArg::make(
            [
                'included' => $queryArray['include'] ?? [],
                'excluded' => $queryArray['exclude'] ?? [],
            ],
            [
                'includeKey' => 'included',
                'excludeKey' => 'excluded',
            ]
        );

        $this->roleQuery = IncludeExcludeIsQueryArg::make(
            [
                'included' => $queryArray['role__in'] ?? [],
                'excluded' => $queryArray['role__not_in'] ?? [],
                'is' => isset($queryArray['role'])
                    ? (array) $queryArray['role']
                    : null,
            ],
            [
                'isIdentifier' => false,
                'multiValue' => true,
                'includeKey' => 'included',
                'excludeKey' => 'excluded',
            ]
        );

        $this->capabilityQuery = IncludeExcludeIsQueryArg::make(
            [
                'included' => $queryArray['capability__in'] ?? [],
                'excluded' => $queryArray['capability__not_in'] ?? [],
                'is' => isset($queryArray['capability'])
                    ? (array) $queryArray['capability']
                    : null,
            ],
            [
                'isIdentifier' => false,
                'multiValue' => true,
                'includeKey' => 'included',
                'excludeKey' => 'excluded',
            ]
        );

        $this->loginQuery = IncludeExcludeIsQueryArg::make(
            [
                'included' => $queryArray['login__in'] ?? [],
                'excluded' => $queryArray['login__not_in'] ?? [],
                'is' => $queryArray['login'] ?? null,
            ],
            [
                'isIdentifier' => false,
                'includeKey' => 'included',
                'excludeKey' => 'excluded',
            ]
        );

        $this->nicenameQuery = IncludeExcludeIsQueryArg::make(
            [
                'included' => $queryArray['nicename__in'] ?? [],
                'excluded' => $queryArray['nicename__not_in'] ?? [],
                'is' => $queryArray['nicename'] ?? null,
            ],
            [
                'isIdentifier' => false,
                'includeKey' => 'included',
                'excludeKey' => 'excluded',
            ]
        );

        $this->searchQuery = SearchQueryArg::make(
            [
                'search' => $queryArray['search'] ?? '',
                'searchColumns' => $queryArray['search_columns'] ?? [],
            ],
            [
                'allowedSearchColumns' => [
                    'ID',
                    'user_login',
                    'user_email',
                    'user_url',
                    'user_nicename',
                    'display_name',
                ],
            ]
        );

        $this->metaQuery = MetaQueryArg::make(
            $queryArray['meta_query'] ?? []
        );

        if (isset($queryArray['orderby'])) {
            $this->orderBy = $queryArray['orderby'];
        }

        if (isset($queryArray['order'])) {
            $this->order = 'ASC' === \strtoupper($queryArray['order'])
                ? 'ASC'
                : 'DESC';
        }

        if (isset($queryArray['number'])) {
            $number = (int) $queryArray['number'];
            $this->number = -1 === $number ? -1 : \max(0, $number);
        }

        if (isset($queryArray['offset'])) {
            $this->offset = \max(0, (int) $queryArray['offset']);
        }

        if (\array_key_exists('fields', $queryArray)) {
            $this->fields = $queryArray['fields'];
        }

        if (isset($queryArray['count_total'])) {
            $this->countTotal = (bool) $queryArray['count_total'];
        }

        if (isset($queryArray['blog_id'])) {
            $this->blogId = (int) $queryArray['blog_id'];
        }

        if (isset($queryArray['meta_key'])) {
            $this->metaKey = $queryArray['meta_key'];
        }

        if (\array_key_exists('meta_value', $queryArray)) {
            $this->metaValue = $queryArray['meta_value'];
            $this->hasMetaValue = true;
        }

        if (isset($queryArray['meta_compare'])) {
            $this->metaCompare = (string) $queryArray['meta_compare'];
        }

        if (isset($queryArray['meta_type'])) {
            $this->metaType = (string) $queryArray['meta_type'];
        }

        if (isset($queryArray['meta_compare_key'])) {
            $this->metaCompareKey = (string) $queryArray['meta_compare_key'];
        }

        if (isset($queryArray['meta_type_key'])) {
            $this->metaTypeKey = (string) $queryArray['meta_type_key'];
        }

        if (isset($queryArray['paged'])) {
            $this->paged = \max(1, (int) $queryArray['paged']);
        }

        if (isset($queryArray['who'])) {
            $this->who = (string) $queryArray['who'];
        }

        if (\array_key_exists('has_published_posts', $queryArray)) {
            $value = $queryArray['has_published_posts'];
            $this->hasPublishedPosts = \is_bool($value) || null === $value
                ? $value
                : (array) $value;
        }

        if (isset($queryArray['cache_results'])) {
            $this->cacheResults = (bool) $queryArray['cache_results'];
        }
    }

    public static function make(array $queryArray = [], array $args = []): static
    {
        return new static($queryArray);
    }

    public function includeIds(int|float|string|array $ids): self
    {
        $this->idQuery->include($ids);

        return $this;
    }

    public function excludeIds(int|float|string|array $ids): self
    {
        $this->idQuery->exclude($ids);

        return $this;
    }

    public function role(string|array $roles): self
    {
        $this->roleQuery->is(
            \is_array($roles) ? $roles : [$roles]
        );

        return $this;
    }

    public function rolesIn(string|array $roles): self
    {
        $this->roleQuery->include($roles);

        return $this;
    }

    public function rolesNotIn(string|array $roles): self
    {
        $this->roleQuery->exclude($roles);

        return $this;
    }

    public function capability(string|array $capabilities): self
    {
        $this->capabilityQuery->is(\is_array($capabilities) ? $capabilities : [$capabilities]);

        return $this;
    }

    public function capabilitiesIn(string|array $capabilities): self
    {
        $this->capabilityQuery->include($capabilities);

        return $this;
    }

    public function capabilitiesNotIn(string|array $capabilities): self
    {
        $this->capabilityQuery->exclude($capabilities);

        return $this;
    }

    public function blogId(int $blogId): self
    {
        $this->blogId = $blogId;

        return $this;
    }

    public function metaKey(string|array $key): self
    {
        $this->metaKey = $key;

        return $this;
    }

    public function metaValue(mixed $value): self
    {
        $this->metaValue = $value;
        $this->hasMetaValue = true;

        return $this;
    }

    public function metaCompare(string $comparison): self
    {
        $this->metaCompare = $comparison;

        return $this;
    }

    public function metaType(string $type): self
    {
        $this->metaType = $type;

        return $this;
    }

    public function metaCompareKey(string $comparison): self
    {
        $this->metaCompareKey = $comparison;

        return $this;
    }

    public function metaTypeKey(string $type): self
    {
        $this->metaTypeKey = $type;

        return $this;
    }

    public function paged(int $page): self
    {
        $this->paged = \max(1, $page);

        return $this;
    }

    public function who(string $who): self
    {
        $this->who = $who;

        return $this;
    }

    public function hasPublishedPosts(bool|array|null $postTypes): self
    {
        $this->hasPublishedPosts = $postTypes;

        return $this;
    }

    public function cacheResults(bool $cache = true): self
    {
        $this->cacheResults = $cache;

        return $this;
    }

    public function login(string $login): self
    {
        $this->loginQuery->is($login);

        return $this;
    }

    public function loginsIn(string|array $logins): self
    {
        $this->loginQuery->include($logins);

        return $this;
    }

    public function loginsNotIn(string|array $logins): self
    {
        $this->loginQuery->exclude($logins);

        return $this;
    }

    public function nicename(string $nicename): self
    {
        $this->nicenameQuery->is($nicename);

        return $this;
    }

    public function nicenamesIn(string|array $nicenames): self
    {
        $this->nicenameQuery->include($nicenames);

        return $this;
    }

    public function nicenamesNotIn(string|array $nicenames): self
    {
        $this->nicenameQuery->exclude($nicenames);

        return $this;
    }

    public function search(string $value): self
    {
        $this->searchQuery->search($value);

        return $this;
    }

    public function searchColumns(string|array $columns): self
    {
        $this->searchQuery->searchColumns($columns);

        return $this;
    }

    public function searchByLogin(string $value): self
    {
        return $this->search($value)
            ->searchColumns('user_login');
    }

    public function searchByEmail(string $value): self
    {
        return $this->search($value)
            ->searchColumns('user_email');
    }

    public function searchByUrl(string $value): self
    {
        return $this->search($value)
            ->searchColumns('user_url');
    }

    public function searchByNicename(string $value): self
    {
        return $this->search($value)
            ->searchColumns('user_nicename');
    }

    public function searchByDisplayName(string $value): self
    {
        return $this->search($value)
            ->searchColumns('display_name');
    }

    public function orderBy(string|array $field, string $direction = 'ASC'): self
    {
        $this->orderBy = $field;
        $this->order = 'ASC' === \strtoupper($direction)
            ? 'ASC'
            : 'DESC';

        return $this;
    }

    public function limit(int $number): self
    {
        $this->number = -1 === $number ? -1 : \max(0, $number);

        return $this;
    }

    public function offset(int $number): self
    {
        $this->offset = \max(0, $number);

        return $this;
    }

    public function select(string|array $fields): self
    {
        $this->fields = $fields;

        return $this;
    }

    public function countTotal(bool $count = true): self
    {
        $this->countTotal = $count;

        return $this;
    }

    public function where(
        string $key,
        mixed $operator,
        mixed $value = null,
        string $type = 'CHAR'
    ): self {
        if (2 === \func_num_args()) {
            $this->metaQuery->where($key, $operator);
        } elseif (3 === \func_num_args()) {
            $this->metaQuery->where($key, $operator, $value);
        } else {
            $this->metaQuery->where($key, $operator, $value, $type);
        }

        return $this;
    }

    public function orWhere(
        string $key,
        mixed $operator,
        mixed $value = null,
        string $type = 'CHAR'
    ): self {
        if (2 === \func_num_args()) {
            $this->metaQuery->orWhere($key, $operator);
        } elseif (3 === \func_num_args()) {
            $this->metaQuery->orWhere($key, $operator, $value);
        } else {
            $this->metaQuery->orWhere($key, $operator, $value, $type);
        }

        return $this;
    }

    public function whereIn(
        string $key,
        array $values,
        string $type = 'CHAR'
    ): self {
        $this->metaQuery->whereIn($key, $values, $type);

        return $this;
    }

    public function whereNotIn(
        string $key,
        array $values,
        string $type = 'CHAR'
    ): self {
        $this->metaQuery->whereNotIn($key, $values, $type);

        return $this;
    }

    public function whereBetween(
        string $key,
        array $values,
        string $type = 'CHAR'
    ): self {
        $this->metaQuery->whereBetween($key, $values, $type);

        return $this;
    }

    public function whereNotBetween(
        string $key,
        array $values,
        string $type = 'CHAR'
    ): self {
        $this->metaQuery->whereNotBetween($key, $values, $type);

        return $this;
    }

    public function whereLike(string $key, string $value): self
    {
        $this->metaQuery->whereLike($key, $value);

        return $this;
    }

    public function whereNotLike(string $key, string $value): self
    {
        $this->metaQuery->whereNotLike($key, $value);

        return $this;
    }

    public function whereRegexp(string $key, string $pattern): self
    {
        $this->metaQuery->whereRegexp($key, $pattern);

        return $this;
    }

    public function whereNotRegexp(string $key, string $pattern): self
    {
        $this->metaQuery->whereNotRegexp($key, $pattern);

        return $this;
    }

    public function whereExists(string $key): self
    {
        $this->metaQuery->whereExists($key);

        return $this;
    }

    public function whereNotExists(string $key): self
    {
        $this->metaQuery->whereNotExists($key);

        return $this;
    }

    public function whereMetaKey(
        string $key,
        string $comparison = '=',
        string $type = ''
    ): self {
        $this->metaQuery->whereMetaKey($key, $comparison, $type);

        return $this;
    }

    public function whereGroup(
        callable $callback,
        string $relation = 'AND'
    ): self {
        $this->metaQuery->whereGroup($callback, $relation);

        return $this;
    }

    public function orWhereGroup(
        callable $callback,
        string $relation = 'AND'
    ): self {
        $this->metaQuery->orWhereGroup($callback, $relation);

        return $this;
    }

    public function whereAny(callable $callback): self
    {
        $this->metaQuery->whereAny($callback);

        return $this;
    }

    public function whereAll(callable $callback): self
    {
        $this->metaQuery->whereAll($callback);

        return $this;
    }

    public function whereClause(
        array $clause,
        string $boolean = 'AND'
    ): self {
        $this->metaQuery->whereClause($clause, $boolean);

        return $this;
    }

    public function toArray(): array
    {
        $args = [];

        $idQuery = $this->idQuery->toArray();

        if (isset($idQuery['included'])) {
            $args['include'] = $idQuery['included'];
        }

        if (isset($idQuery['excluded'])) {
            $args['exclude'] = $idQuery['excluded'];
        }

        $roleQuery = $this->roleQuery->toArray();

        if (isset($roleQuery['included'])) {
            $args['role__in'] = $roleQuery['included'];
        }

        if (isset($roleQuery['excluded'])) {
            $args['role__not_in'] = $roleQuery['excluded'];
        }

        if (isset($roleQuery['is'])) {
            $args['role'] = $roleQuery['is'];
        }

        $capabilityQuery = $this->capabilityQuery->toArray();

        if (isset($capabilityQuery['included'])) {
            $args['capability__in'] = $capabilityQuery['included'];
        }

        if (isset($capabilityQuery['excluded'])) {
            $args['capability__not_in'] = $capabilityQuery['excluded'];
        }

        if (isset($capabilityQuery['is'])) {
            $args['capability'] = $capabilityQuery['is'];
        }

        $loginQuery = $this->loginQuery->toArray();

        if (isset($loginQuery['included'])) {
            $args['login__in'] = $loginQuery['included'];
        }

        if (isset($loginQuery['excluded'])) {
            $args['login__not_in'] = $loginQuery['excluded'];
        }

        if (isset($loginQuery['is'])) {
            $args['login'] = $loginQuery['is'];
        }

        $nicenameQuery = $this->nicenameQuery->toArray();

        if (isset($nicenameQuery['included'])) {
            $args['nicename__in'] = $nicenameQuery['included'];
        }

        if (isset($nicenameQuery['excluded'])) {
            $args['nicename__not_in'] = $nicenameQuery['excluded'];
        }

        if (isset($nicenameQuery['is'])) {
            $args['nicename'] = $nicenameQuery['is'];
        }

        $searchQuery = $this->searchQuery->toArray();

        if (isset($searchQuery['search'])) {
            $args['search'] = $searchQuery['search'];
        }

        if (isset($searchQuery['searchColumns'])) {
            $args['search_columns'] = $searchQuery['searchColumns'];
        }

        if (null !== $this->orderBy) {
            $args['orderby'] = $this->orderBy;
        }

        if (null !== $this->order) {
            $args['order'] = $this->order;
        }

        if (null !== $this->number) {
            $args['number'] = $this->number;
        }

        if (null !== $this->offset) {
            $args['offset'] = $this->offset;
        }

        if (null !== $this->fields) {
            $args['fields'] = $this->fields;
        }

        if (null !== $this->countTotal) {
            $args['count_total'] = $this->countTotal;
        }

        if (null !== $this->blogId) {
            $args['blog_id'] = $this->blogId;
        }

        if (null !== $this->metaKey) {
            $args['meta_key'] = $this->metaKey;
        }

        if ($this->hasMetaValue) {
            $args['meta_value'] = $this->metaValue;
        }

        if (null !== $this->metaCompare) {
            $args['meta_compare'] = $this->metaCompare;
        }

        if (null !== $this->metaType) {
            $args['meta_type'] = $this->metaType;
        }

        if (null !== $this->metaCompareKey) {
            $args['meta_compare_key'] = $this->metaCompareKey;
        }

        if (null !== $this->metaTypeKey) {
            $args['meta_type_key'] = $this->metaTypeKey;
        }

        if (null !== $this->paged) {
            $args['paged'] = $this->paged;
        }

        if (null !== $this->who) {
            $args['who'] = $this->who;
        }

        if (null !== $this->hasPublishedPosts) {
            $args['has_published_posts'] = $this->hasPublishedPosts;
        }

        if (null !== $this->cacheResults) {
            $args['cache_results'] = $this->cacheResults;
        }

        $metaQuery = $this->metaQuery->toArray();

        if ([] !== $metaQuery) {
            $args['meta_query'] = $metaQuery;
        }

        return $this->mergeQueryArguments($args);
    }
}
