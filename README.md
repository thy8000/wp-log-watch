# WP Log Watch

Audit log plugin for WordPress. Tracks content, user, system and security
events and protects the records against unauthorized access.

WP Log Watch records *who* did *what*, to *which resource*, *when* and with
*which metadata* — without depending on WordPress inside the domain layer.

## Requirements

- PHP 8.2+
- WordPress 6.0+
- MySQL (a dedicated table is created on activation)

## Architecture

The plugin follows Clean Architecture with four layers and PSR-4 namespaces
(`WPLogWatch\`):

```
src/
├── Domain/            Entities, value objects, repository port. No WordPress.
├── Application/       Use cases, DTOs, services (sensitive data filter, clock).
├── Infrastructure/    WordPress hooks, $wpdb repository, cron, providers.
├── Presentation/      Admin UI, REST API, views.
└── Shared/            Service container.
```

The rule of dependency: `Presentation → Application → Domain ← Infrastructure`.
The Domain layer never calls WordPress functions such as `get_current_user_id()`,
`wp_insert_post()` or `$wpdb`.

## Events

- **Content** — post/page creation, updates (title/author/slug diff), status
  transitions (publish, draft, trash, restore), deletion, term
  (category/tag) changes and a curated allowlist of post meta.
- **Users** — creation, profile updates, deletion, login, logout, failed
  logins and role changes.
- **System** — plugin activation/deactivation/install/update/removal, theme
  switches, WordPress core updates.
- **Security** — failed login attempts, privilege changes and the origin
  (context, IP, user agent) of every event.

## Database

A dedicated table `{prefix}wp_log_watch_audit_logs` is created with `dbDelta()`:

| Column         | Type          |
| -------------- | ------------- |
| id             | BIGINT        |
| event_uuid     | CHAR(36)      |
| actor_id       | BIGINT        |
| event_type     | VARCHAR(100)  |
| resource_type  | VARCHAR(100)  |
| resource_id    | BIGINT        |
| description    | TEXT          |
| metadata       | LONGTEXT (JSON) |
| ip_address     | VARCHAR(45)   |
| user_agent     | TEXT          |
| created_at     | DATETIME      |

## Security

- Records are immutable from the admin UI (read-only).
- Access is protected by the `view_wp_log_watch_logs` capability; settings by
  `manage_wp_log_watch_settings`.
- Every input is validated and every output is escaped.
- All SQL uses `$wpdb->prepare()`; dynamic identifiers are allowlisted.
- Admin actions verify capabilities and nonces.
- Sensitive values (passwords, tokens, cookies…) are redacted before persistence.
- A retention policy removes old entries via a daily WP-Cron job (default 90 days).

## Development

```sh
composer install
composer test      # PHPUnit
composer analyse   # PHPStan
composer lint      # PHP_CodeSniffer (WordPress Coding Standards)
```

Unit tests for the Domain and Application layers run without a WordPress
installation.

## License

GPL-2.0-or-later. See [LICENSE](LICENSE).
