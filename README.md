# Pentarix WordPress Database

Composer-installable WordPress query argument builders with a fluent API.

This package provides the WordPress query argument-building capabilities extracted from Vertex Framework. It builds argument arrays for WordPress APIs; it does not execute queries or provide an ORM.

## Requirements

- PHP 8.0 or later
- WordPress 6.3 or later when passing generated arguments to WordPress APIs

## Installation

```bash
composer require pentarix/wordpress-database
```

Composer maps the `Pentarix\WordPressDatabase\` namespace to `src/` using PSR-4.

## Usage

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

The builders produce argument arrays for WordPress query APIs, including:

- `WP_Query`
- `WP_User_Query`
- `WP_Comment_Query`
- `WP_Term_Query`
- `WP_Site_Query`
- `WP_Network_Query`

They do not execute database queries, replace WordPress query classes, or bypass the WordPress query lifecycle. Direct SQL, custom-table CRUD, repositories, models, and migrations are outside the current scope.

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
