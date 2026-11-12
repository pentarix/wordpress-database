# Pentarix WordPress Database

Composer-installable WordPress query argument builders and native query execution helpers with a fluent API.

The package provides WordPress query argument builders and a small execution layer that delegates to WordPress Core query classes. It does not provide an ORM or replace the WordPress query lifecycle.

## Requirements

- PHP 8.0 or later
- WordPress 6.3 or later when executing queries or passing generated arguments to WordPress APIs

## Installation

```bash
composer require pentarix/wordpress-database
```

Composer maps the `Pentarix\WordPressDatabase\` namespace to `src/` using PSR-4.

## Usage

Build arguments independently from WordPress, then pass them to the native query API:

```php
use Pentarix\WordPressDatabase\WordPress\MetaQueryArg;
use Pentarix\WordPressDatabase\WordPress\PostQueryArg;

$args = PostQueryArg::make()
    ->postType('product')
    ->postStatus('publish')
    ->postsPerPage(12)
    ->metaQuery(MetaQueryArg::make()->where('featured', true))
    ->toArray();

$query = new \WP_Query($args);
```

For a convenient execution API, use `PostQuery`:

```php
use Pentarix\WordPressDatabase\WordPress\PostQuery;
use Pentarix\WordPressDatabase\WordPress\PostQueryArg;

$query = PostQuery::make(
    PostQueryArg::make()
        ->postType('product')
        ->postStatus('publish')
        ->postsPerPage(12)
);

$wpQuery = $query->execute(); // WP_Query
$posts = $query->get();       // Current page of posts
$first = $query->first();     // First result of the configured query, or null
$total = $query->count();     // Total matches, independent of page size
```

`execute()` returns the native `WP_Query` instance. The result helpers run their own query and return WordPress results; they do not introduce custom post models or collections. `count()` enables found-row calculation even when the original arguments set `no_found_rows` to `true`. The `first()` result follows the configured `fields` argument.

### Pagination

Use `paginate()` when both the current page and total-page metadata are required:

```php
$results = $query->paginate(perPage: 12, page: 2);

$posts = $results->posts;
$total = $results->found_posts;
$totalPages = $results->max_num_pages;
```

The method returns the native `WP_Query` object. It sets `no_found_rows` to `false` so WordPress calculates `found_posts` and `max_num_pages`, and it overrides any existing `posts_per_page` and `paged` values. Both arguments must be positive integers.

WordPress does not reliably combine an explicit `offset` with `paged` pagination. Therefore, `paginate()` removes an existing `offset` argument. Use ordinary `execute()` when you need a manually controlled offset instead of page-based pagination.

The execution layer requires WordPress to be loaded. Argument builders remain independently usable without a WordPress runtime.

### User queries

Use `UserQuery` to execute a `WP_User_Query` while retaining WordPress's native result contract:

```php
use Pentarix\WordPressDatabase\WordPress\UserQuery;
use Pentarix\WordPressDatabase\WordPress\UserQueryArg;

$query = UserQuery::make(
    UserQueryArg::make()
        ->role('editor')
        ->orderBy('ID', 'ASC')
        ->limit(20)
);

$wpQuery = $query->execute(); // WP_User_Query
$users = $query->get();       // Results for the configured query
$first = $query->first();     // First result, or null
$total = $query->count();     // Total matching users
```

`get()` returns the native results from `WP_User_Query::get_results()`; the result shape follows the configured `fields` argument. `first()` also respects `fields`, while `count()` enables `count_total` and returns `get_total()`, regardless of the configured page size.

For pagination, use the native query object and its result/count methods:

```php
$results = $query->paginate(perPage: 20, page: 2);

$users = $results->get_results();
$total = $results->get_total();
```

`paginate()` sets `number`, `paged`, and `count_total`, and returns the native `WP_User_Query` instance. Both pagination arguments must be positive integers. An existing `offset` is removed because WordPress gives it precedence over the page-based offset calculation; use `execute()` when you need a manually controlled offset.

## Available builders

- `CommentQueryArg`
- `DateQueryArg`
- `MetaQueryArg`
- `NetworkQueryArg`
- `PostQueryArg`
- `SiteQueryArg`
- `TaxQueryArg`
- `TermQueryArg`
- `UserQueryArg`

Supporting builders (`IncludeQueryArg`, `IncludeExcludeQueryArg`, `IncludeExcludeIsQueryArg`, and `SearchQueryArg`) are also available under `Pentarix\WordPressDatabase\WordPress`.

## Argument coverage

The main query builders retain arguments supplied to their constructors and expose `set()` and `setMany()` for query variables that do not have a dedicated convenience method:

```php
$args = PostQueryArg::make()
    ->set('author', 12)
    ->set('post_mime_type', ['image/jpeg', 'image/png'])
    ->setMany([
        'search_columns' => ['post_title', 'post_excerpt'],
        'update_menu_item_cache' => false,
    ])
    ->toArray();
```

Builder-specific fluent methods remain the preferred interface for commonly used arguments and nested query clauses. Generic setters provide pass-through coverage for additional core arguments and custom query variables supported by WordPress hooks.

Values are passed to WordPress as supplied. WordPress remains responsible for validating, interpreting, and executing query arguments. The builders do not guarantee that arbitrary custom query variables are supported by WordPress Core.

## Query composition

`MetaQueryArg`, `TaxQueryArg`, and `DateQueryArg` build the nested argument structures consumed by WordPress query APIs. Their fluent methods compose conditions and groups; they do not generate SQL.

For example:

```php
use Pentarix\WordPressDatabase\WordPress\MetaQueryArg;
use Pentarix\WordPressDatabase\WordPress\PostQueryArg;
use Pentarix\WordPressDatabase\WordPress\TaxQueryArg;

$args = PostQueryArg::make()
    ->metaQuery(
        MetaQueryArg::make()
            ->where('visibility', 'public')
            ->orWhere('featured', 'yes')
    )
    ->taxQuery(
        TaxQueryArg::make()
            ->where('category', [12])
            ->orWhere('post_tag', 'news', 'slug')
    )
    ->toArray();

$query = new \WP_Query($args);
```

## Scope

The argument builders produce arrays for WordPress query APIs, including:

- `WP_Query`
- `WP_User_Query`
- `WP_Comment_Query`
- `WP_Term_Query`
- `WP_Site_Query`
- `WP_Network_Query`

The execution helpers currently cover `WP_Query` and `WP_User_Query`. Other query APIs retain their native WordPress execution paths until their distinct result contracts are designed.

The package does not replace WordPress query classes or bypass the WordPress query lifecycle. Direct SQL, custom-table CRUD, repositories, models, and migrations are outside the current scope.

## Development

Install dependencies and run the standalone unit tests:

```bash
composer install
composer test
```

The WordPress Core integration suite requires a MySQL database and a local WordPress test environment. The installer downloads the requested WordPress release and its matching test suite:

```bash
composer update --with phpunit/phpunit:^9.6
bash bin/install-wp-tests.sh wordpress_test root root 127.0.0.1:3306 latest
composer test:integration
```

Set `WP_TESTS_DIR` and `WP_CORE_DIR` if the test suite or WordPress installation should use non-default locations. The integration suite enables multisite in its test configuration to cover site and network query APIs.

GitHub Actions runs the unit suite on PHP 8.0–8.5 and the integration suite against WordPress 6.3 and the latest stable release.

## Licence

MIT. See [LICENSE](LICENSE).
