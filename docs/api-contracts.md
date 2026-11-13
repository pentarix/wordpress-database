# API Contracts

This document records the public contracts that consumers should rely on. The package intentionally keeps WordPress Core responsible for Core data semantics.

## Runtime requirements

- PHP 8.0 or later.
- WordPress 6.3 or later for operations that call WordPress.
- Query argument builders can be used without a loaded WordPress runtime. Entity wrappers, query execution, models, menus, and custom-table access require WordPress.

## Query argument builders and execution

The `*QueryArg` classes build argument arrays for the corresponding WordPress query APIs. They do not generate SQL.

The query execution classes delegate to the native query classes:

| Package class | WordPress Core class |
| --- | --- |
| `PostQuery` | `WP_Query` |
| `UserQuery` | `WP_User_Query` |
| `CommentQuery` | `WP_Comment_Query` |
| `TermQuery` | `WP_Term_Query` |
| `SiteQuery` | `WP_Site_Query` |
| `NetworkQuery` | `WP_Network_Query` |

Execution methods return the native query object or native result shape documented by the corresponding WordPress API. The package does not normalise different query result contracts into a common collection.

## Core entity wrappers

Entity wrappers delegate to WordPress Core and preserve its return values, errors, filters, and lifecycle hooks. They do not perform capability checks or define an authorisation policy; callers remain responsible for permissions.

The `Model` API is a lightweight stateful post model. It is not a general ORM. Its post fields and metadata are persisted through the existing `Post` and `Metadata` wrappers, and its relationships return native WordPress objects unless explicitly documented otherwise.

The `Menu` and `MenuItem` wrappers cover classic navigation menus. Menu item queries return the decorated objects produced by `wp_get_nav_menu_items()`; `MenuItem::get()` returns the underlying `WP_Post`.

Menu creation, updates, and item mutations accept ordinary unslashed input. The wrappers apply WordPress's required slashing internally. `Menu::update()` accepts `name` as a convenience alias for the native `menu-name` key; it returns the native menu ID or `WP_Error` from `wp_update_nav_menu_object()`.

## Custom tables

`CustomTable::forSite($suffix)` creates a helper for a table using the current site's prefix. `CustomTable::forNetwork($suffix)` uses the Multisite base prefix.

Only a table suffix is accepted. It must match letters, numbers, and underscores; the WordPress prefix is added by the package. Do not pass a fully prefixed table name.

### CRUD return values

| Method | Return value |
| --- | --- |
| `insert()` | Insert ID, or `false` on failure |
| `update()` | Number of affected rows, or `false` on failure |
| `delete()` | Number of deleted rows, or `false` on failure |

An affected-row count of `0` is not a database error. It means that no rows were changed or deleted.

### Prepared queries

`prepare()`, `execute()`, `getResults()`, `getRow()`, and `getVar()` delegate to the active `wpdb` connection.

- Use `%d`, `%f`, and `%s` for values.
- Use `%i` for identifiers. This placeholder is available from WordPress 6.2.
- Numbered placeholders and WordPress formatting modifiers are supported by the underlying `wpdb::prepare()` implementation.
- Pass placeholders and values separately in the `$args` array.
- Do not concatenate untrusted values or identifiers into SQL.
- If a query contains placeholders, arguments are required. Supplying arguments without placeholders is rejected.
- Queries without placeholders may be passed without arguments.

The query methods return the native `wpdb` result shape. In particular, `execute()` returns the result from `wpdb::query()`, and read methods preserve the native `wpdb` results.

### Schema ownership

The consumer owns the custom table schema. Use WordPress schema tools such as `dbDelta()` from the plugin or theme that owns the table. `CustomTable` does not create or alter tables, run migrations, manage transactions, infer relationships, or connect to a separate database.

Do not use `CustomTable` to read or write WordPress Core tables. Use the relevant Core wrapper instead.

## Errors and validation

- Native `WP_Error`, `false`, `null`, IDs, counts, and objects are preserved where the underlying Core API returns them.
- Wrappers throw `LogicException` when called before the required WordPress runtime is loaded.
- `CustomTable` throws `InvalidArgumentException` for invalid table suffixes, missing arguments for placeholders, and supplied arguments when a query has no placeholders. WordPress remains responsible for validating the placeholder count and formatting.
- The package does not silently convert database failures into empty result sets or success values.

## Explicit non-goals

The public API does not aim to provide:

- A general-purpose ORM or Active Record implementation.
- A SQL query builder or cross-database abstraction.
- Schema migrations or a schema definition language.
- Custom database connections or connection pooling.
- Automatic capability checks, authentication, or authorisation.
- Registration APIs for post types, taxonomies, roles, or theme locations.
- Direct SQL access to WordPress Core tables.
