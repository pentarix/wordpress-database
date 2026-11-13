# WordPress Core Data API Audit

**Reviewed against:** WordPress 6.3 and the current Core API surface used by this package.

## Conclusion

The package now covers the principal persistent WordPress data entities that have suitable public Core APIs. The wrappers deliberately delegate to those APIs instead of reproducing their persistence rules or accessing Core tables directly.

The remaining gaps are mostly boundaries between database records and other WordPress subsystems. They do not justify adding another generic abstraction to this package.

## Coverage matrix

| Data area | Current coverage | Assessment |
| --- | --- | --- |
| Posts and custom post types | `Post`, `PostQuery`, `PostQueryArg`, and the lightweight `Model` API | Covered. Custom post types use the same native post APIs. |
| Attachments and media | `Attachment` and `Post` | Covered. Attachments are posts with the `attachment` post type; media-specific operations delegate to Core. |
| Revisions and autosaves | `Revision` | Covered. |
| Users | `User` and `UserQuery` | Covered, including roles, capabilities, passwords, and Multisite membership operations. |
| Terms and taxonomies | `Term` and `TermQuery` | Covered for term CRUD, hierarchy, relationships, and taxonomy lookup. Taxonomy registration remains the caller's responsibility. |
| Comments | `Comment` and `CommentQuery` | Covered, including moderation, replies, and comment counts. |
| Classic navigation menus | `Menu` and `MenuItem` | Covered by the Navigation Menu API. Menu items are returned in the decorated format supplied by Core when queried through a menu. |
| Sites (blogs) | `Site` and `SiteQuery` | Covered for native site operations, context switching, URLs, and site options. |
| Networks | `Network` and `NetworkQuery` | Read operations and network options are covered. Core does not provide a corresponding general-purpose network create/update/delete API. |
| Options | `Option` | Covered for current-site, network-wide, and per-blog options. |
| Metadata | `Metadata` | Covered through the generic Metadata API for supported Core object types, including posts, comments, terms, users, individual sites (the `blog` meta type), and networks (the `site` meta type). |
| Transients | `Transient` | Covered for site and network transients. |
| Legacy bookmarks | `Link` | Covered through the legacy Bookmarks API. This API is retained for compatibility and is not a recommended foundation for new navigation features. |
| Custom tables | `CustomTable` | Covered for application-owned tables through the active `$wpdb` connection, including prefixed site/network table selection, CRUD, and prepared queries. Schema management and migrations remain outside scope. |

## Deliberate boundaries

### Theme menu locations

A menu and its items are persistent navigation entities. A theme location is different: themes register location identifiers, and WordPress stores the selected menu assignments in theme mods.

The `Menu` and `MenuItem` wrappers therefore do not register theme locations or expose theme configuration as menu CRUD. Themes should continue to use `register_nav_menus()`, `get_nav_menu_locations()`, and the Theme API for location assignments.

### Block-based navigation

Block themes can store navigation structures as `wp_navigation` posts. These records are already accessible through the generic `Post` and `PostQuery` APIs. A second navigation-specific persistence layer is not currently justified. The classic Navigation Menu API remains a separate Core feature and is covered by `Menu` and `MenuItem`.

### Authentication state

WordPress session tokens and Application Passwords are authentication/security facilities, not general-purpose content entities. Their storage is managed by Core and may use user metadata internally. This package does not expose token creation, revocation, or credential management as database CRUD. Callers should use the dedicated WordPress authentication APIs and their security lifecycle.

### Cache and scheduled events

The Object Cache API, cron scheduling, and other runtime facilities are not treated as relational data entities. Transients are included because WordPress explicitly exposes them as a persistent key/value API; the Object Cache API remains outside this package's scope.

### Registration and configuration APIs

Registering post types, taxonomies, roles, capabilities, theme locations, widgets, and similar runtime structures is configuration, not CRUD for stored database entities. Those operations remain with the relevant WordPress APIs.

### Network records

WordPress exposes network retrieval and network options, but no general native API for creating, updating, or deleting network records. The package will not simulate those operations with direct SQL.

## Custom-table boundary

The Custom Table API is implemented and intentionally separate from the Core entity wrappers:

- Core posts, users, terms, comments, sites, and networks continue to use WordPress's native APIs.
- Custom tables use the active `$wpdb` connection and its prepared-query and CRUD methods.
- Table identifiers are validated and prefixed by WordPress; values are passed through WordPress's database preparation and format handling.
- This API does not introduce a general ORM, relationship mapper, schema migration engine, or replacement database connection.

Schema creation and migrations require a separate design decision and are not part of the initial Custom Table API.

## Audit decision

No additional general-purpose ORM or duplicate Core entity layer is required. The agreed persistent data-access scope is implemented. Custom-table schema management and migrations remain intentionally outside this package's scope. Authentication, theme configuration, registration, caching, and scheduling remain explicit integration boundaries rather than missing database wrappers.
