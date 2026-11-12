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
