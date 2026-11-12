# Pentarix WordPress Database

Composer-installable WordPress query argument builders with a fluent API.

This package currently provides the WordPress query argument-building capabilities extracted from Vertex Framework. It builds argument arrays; it does not execute queries or provide an ORM.

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

$query = new \\WP_Query($args);
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

The main query builders retain the arguments supplied to their constructors and expose
`set()` and `setMany()` for WordPress query variables that do not have a dedicated
convenience method yet. These methods preserve the supplied key and value without
rewriting WordPress's query contract:

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

The builder-specific fluent methods remain the preferred interface for commonly used
arguments and nested query clauses. The generic setters provide pass-through coverage
for additional core arguments and custom query variables supported by WordPress hooks.
Values are passed to WordPress as supplied; WordPress remains responsible for query
interpretation and execution.

## Scope

The builders return arrays compatible with WordPress query APIs, including `WP_Query`, `WP_User_Query`, `WP_Comment_Query`, `WP_Term_Query`, and multisite query APIs.

They do not execute database queries, replace WordPress query classes, or bypass WordPress's query lifecycle. Direct SQL, custom-table CRUD, repositories, models, and migrations are outside the current scope.

## Development

```bash
composer install
composer test
```

## Licence

MIT. See [LICENSE](LICENSE).
