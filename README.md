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

### Comment queries

Use `CommentQuery` to execute a `WP_Comment_Query` while retaining WordPress's native result contract:

```php
use Pentarix\WordPressDatabase\WordPress\CommentQuery;
use Pentarix\WordPressDatabase\WordPress\CommentQueryArg;

$query = CommentQuery::make(
    CommentQueryArg::make([
        'post_id' => 42,
        'status' => 'approve',
        'orderby' => 'comment_date_gmt',
        'order' => 'DESC',
    ])
);

$wpQuery = $query->execute(); // WP_Comment_Query
$comments = $query->get();    // Native comment results
$first = $query->first();     // First result, or null
$total = $query->count();     // Total matching comments
```

`get()` preserves the native `fields` and `count` query arguments, so its result is an array or an integer when `count` is enabled. `first()` always requests comment results and respects the configured `fields` argument. `count()` enables found-row calculation and returns the total number of matches, regardless of the configured page size.

For pagination, use the native query object:

```php
$results = $query->paginate(perPage: 20, page: 2);

$comments = $results->comments;
$total = $results->found_comments;
$totalPages = $results->max_num_pages;
```

`paginate()` sets `number`, `paged`, and `no_found_rows` so WordPress calculates pagination metadata. It removes an existing `offset`, because WordPress gives an explicit offset precedence over page-based pagination. Both pagination arguments must be positive integers.

The execution layer requires WordPress to be loaded. Argument builders remain independently usable without a WordPress runtime.

### Term queries

Use `TermQuery` to execute a native `WP_Term_Query` while retaining its result contract:

```php
use Pentarix\WordPressDatabase\WordPress\TermQuery;
use Pentarix\WordPressDatabase\WordPress\TermQueryArg;

$query = TermQuery::make(
    TermQueryArg::make([
        'taxonomy' => 'product_cat',
        'hide_empty' => false,
        'orderby' => 'name',
        'order' => 'ASC',
    ])
);

$wpQuery = $query->execute(); // WP_Term_Query
$terms = $query->get();       // Native result for the configured fields argument
$first = $query->first();     // First result, or null
$total = $query->count();     // Total matching terms
```

`get()` preserves the native `fields` result shape, including the scalar result returned by `fields => 'count'`. `first()` returns the first value in the native result array and returns `null` when the query has no array results. `count()` runs a separate query with `fields => 'count'` and returns the total as an integer.

Term queries use `number` and `offset`, not page-based pagination. The `paginate()` helper translates a positive page and page size into those native arguments and returns the native `WP_Term_Query` object:

```php
$results = $query->paginate(perPage: 20, page: 2);

$terms = $results->terms;
$total = $query->count();
```

An existing offset is retained as the starting offset before the page offset is added. WordPress may intentionally omit SQL limits for hierarchical term queries, so `number` and `offset` do not guarantee a page-sized result for every taxonomy or query shape. The helper does not invent pagination metadata; call `count()` separately when the total is required.

The execution layer requires WordPress to be loaded. Argument builders remain independently usable without a WordPress runtime.

### Site queries

Use `SiteQuery` to execute a native `WP_Site_Query` while retaining its result contract:

```php
use Pentarix\WordPressDatabase\WordPress\SiteQuery;
use Pentarix\WordPressDatabase\WordPress\SiteQueryArg;

$query = SiteQuery::make(
    SiteQueryArg::make([
        'network_id' => 1,
        'public' => 1,
        'archived' => 0,
        'orderby' => 'id',
        'order' => 'ASC',
    ])
);

$wpQuery = $query->execute(); // WP_Site_Query
$sites = $query->get();       // Native site results
$first = $query->first();     // First result, or null
$total = $query->count();     // Total matching sites
```

`get()` preserves the native `count` and `fields` query arguments, returning an integer for `count => true`, site IDs for `fields => 'ids'`, or `WP_Site` objects otherwise. `first()` disables count mode and returns the first result in the configured fields format. `count()` runs a separate count query without a result limit or offset.

For pagination, use the native query object:

```php
$results = $query->paginate(perPage: 20, page: 2);

$sites = $results->sites;
$total = $results->found_sites;
$totalPages = $results->max_num_pages;
```

`paginate()` sets `number`, `offset`, and `no_found_rows` so WordPress calculates pagination metadata. An existing offset is retained as the starting offset before the page offset is added. Both pagination arguments must be positive integers.

The execution layer requires WordPress to be loaded. Argument builders remain independently usable without a WordPress runtime.

### Network queries

Use `NetworkQuery` to execute a native `WP_Network_Query` while retaining its result contract:

```php
use Pentarix\WordPressDatabase\WordPress\NetworkQuery;
use Pentarix\WordPressDatabase\WordPress\NetworkQueryArg;

$query = NetworkQuery::make(
    NetworkQueryArg::make([
        'network__in' => [1, 2],
        'orderby' => 'id',
        'order' => 'ASC',
    ])
);

$wpQuery = $query->execute(); // WP_Network_Query
$networks = $query->get();    // Native network results
$first = $query->first();    // First result, or null
$total = $query->count();    // Total matching networks
```

`get()` preserves the native `count` and `fields` query arguments, returning an integer for `count => true`, network IDs for `fields => 'ids'`, or `WP_Network` objects otherwise. `first()` disables count mode, limits the query to one result, and respects the configured `fields` argument. `count()` runs a separate count query without a result limit or offset.

For pagination, use the native query object:

```php
$results = $query->paginate(perPage: 20, page: 2);

$networks = $results->networks;
$total = $results->found_networks;
$totalPages = $results->max_num_pages;
```

`paginate()` sets `number`, `offset`, and `no_found_rows` so WordPress calculates pagination metadata. An existing offset is retained as the starting offset before the page offset is added. Both pagination arguments must be positive integers.

The execution layer requires WordPress to be loaded. Argument builders remain independently usable without a WordPress runtime.

### Options API

Use `Option` for the native WordPress Options API:

```php
use Pentarix\\WordPressDatabase\\WordPress\\Option;

Option::add('my_plugin_settings', ['enabled' => true], false);
$settings = Option::get('my_plugin_settings', []);
Option::update('my_plugin_settings', ['enabled' => false]);
$exists = Option::exists('my_plugin_settings');
Option::delete('my_plugin_settings');
```

The wrapper delegates to `add_option()`, `get_option()`, `update_option()`, and `delete_option()`. The existence check distinguishes a missing option from an option whose stored value is `false`. Autoload behaviour is passed through to WordPress.

Multisite installations can use the network-wide Site Options API and the options API for a specific site:

```php
// Network-wide options (get_site_option(), add_site_option(), etc.).
Option::addSite('my_network_settings', ['enabled' => true]);
$networkSettings = Option::getSite('my_network_settings', []);
Option::updateSite('my_network_settings', ['enabled' => false]);
$networkOptionExists = Option::existsSite('my_network_settings');
Option::deleteSite('my_network_settings');

// Options belonging to a specific site in the network.
$blogId = 2;
Option::addBlog($blogId, 'my_site_settings', ['enabled' => true]);
$siteSettings = Option::getBlog($blogId, 'my_site_settings', []);
Option::updateBlog($blogId, 'my_site_settings', ['enabled' => false]);
$blogOptionExists = Option::existsBlog($blogId, 'my_site_settings');
Option::deleteBlog($blogId, 'my_site_settings');
```

The getSite()/addSite() methods operate on network-wide options. The *Blog() methods operate on the options table belonging to the specified site. Both groups delegate to WordPress Core and preserve its return values. The existence helpers also recognise stored false values.


### Metadata API

Use `Metadata` to work with metadata for WordPress object types without bypassing the native Metadata API:

```php
use Pentarix\\WordPressDatabase\\WordPress\\Metadata;

Metadata::add('post', $postId, 'featured', true, true);
$isFeatured = Metadata::get('post', $postId, 'featured', true);
Metadata::update('post', $postId, 'featured', false);
$exists = Metadata::exists('post', $postId, 'featured');
Metadata::delete('post', $postId, 'featured');
```

The object type is explicit and is passed to WordPress unchanged. This supports the object types handled by the Core Metadata API, including posts, users, comments, and terms. Use `register()` and `unregister()` to manage registered metadata keys, optionally specifying an object subtype. The wrapper also exposes the native metadata-by-ID operations through `getById()`, `updateById()`, and `deleteById()`; each requires the metadata object type.

### Transients API

Use `Transient` for request/site cache values stored through WordPress's Transients API:

```php
use Pentarix\\WordPressDatabase\\WordPress\\Transient;

Transient::set('my_plugin_cache', $value, 300);
$value = Transient::get('my_plugin_cache');
Transient::delete('my_plugin_cache');

Transient::setSite('my_network_cache', $value, 300);
$value = Transient::getSite('my_network_cache');
Transient::deleteSite('my_network_cache');
```

The expiration is expressed in seconds and follows WordPress semantics. A zero expiration means the transient does not expire. The wrapper does not add an `exists()` method because the native API uses `false` to represent a missing transient, which is indistinguishable from a transient deliberately storing `false`.

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

The execution helpers currently cover `WP_Query`, `WP_User_Query`, `WP_Comment_Query`, `WP_Term_Query`, `WP_Site_Query`, and `WP_Network_Query`.

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
