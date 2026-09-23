# Changelog

All notable changes to LaraPost are documented here.

## [2.0.0] - 2026-09-23

### Added

- TikTok Content Posting API support for video and photo Direct Post using verified HTTPS media URLs.
- Optional MCP server for compatible AI clients, with business context, capabilities, account/history lookup, publishing, scheduling, and cancellation tools.
- `larapost:doctor` diagnostics for database, queue, scheduler, dashboard middleware, platforms, and MCP.
- Selective platform enablement with `LARAPOST_PLATFORMS`.
- Queued publishing with persisted jobs, unique scheduled-post jobs, and retry/backoff handling.
- Token expiry tracking and refresh-before-publish support.
- Custom driver registration through `SocialMedia::extend()`.

### Changed

- PHP 8.3+ is required.
- Laravel 12 and 13 are supported. Laravel 11 support remains on the `1.x` branch.
- Dashboard/operator routes require authentication by default.
- OAuth state validation is strict for built-in OAuth drivers.
- The installation command can configure only the providers and optional surfaces an application actually needs.
- Documentation has been rebuilt around installation, configuration, platforms, publishing, MCP, and upgrading.

### Fixed

- Platform API success envelopes that contain an `error` object with an `ok` / `success` code are no longer treated as failures.
- Scheduled posts are claimed before execution to reduce duplicate publication risk.
- OAuth callback token metadata now preserves expiry information where providers expose it.

## [1.0.0] - 2026-03-30

### Added

- Stable package branding and initial docs.
- Facebook Pages, Twitter / X, and LinkedIn member-profile publishing.
- Dashboard, account connection, scheduled posts, retries, and bulk composer.
- Multi-Page Facebook sync from one OAuth login.

### Fixed

- Facebook publishing uses Page access tokens for Page posts.
- Facebook API versions are normalized.
- Twitter confidential-client OAuth token exchange sends client credentials correctly.
