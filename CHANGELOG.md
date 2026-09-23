# Changelog

All notable changes to LaraPost are documented here.

## [Unreleased]

Nothing yet.

## [2.0.0] - 2026-09-23

### Added

- TikTok Content Posting API support with upload-to-inbox as the safe default for video and photo workflows.
- Explicit TikTok Direct Post mode for applications that implement TikTok's required creator-facing sharing UX.
- Optional MCP server for compatible AI clients, with business context, capabilities, account/history lookup, publishing, scheduling, and cancellation tools.
- `larapost:doctor` diagnostics for database, queue, scheduler, dashboard middleware, TikTok mode, platforms, and MCP.
- Selective platform enablement with `LARAPOST_PLATFORMS`.
- Queued publishing with persisted jobs, unique scheduled-post jobs, and retry/backoff handling.
- Token expiry tracking and refresh-before-publish support.
- Custom driver registration through `SocialMedia::extend()`.
- Rebuilt documentation portal covering installation, configuration, platforms, publishing, AI/MCP, upgrades, releases, and security.

### Changed

- PHP 8.3+ is required.
- Laravel 12 and 13 are supported.
- Dashboard/operator routes require authentication by default.
- OAuth state validation is strict for built-in OAuth drivers.
- The installation command can configure only the providers and optional surfaces an application needs.
- TikTok Direct Post no longer has an implicit privacy default. Direct mode requires explicit consent, privacy, and interaction choices.
- TikTok MCP publishing is limited to upload mode so the creator completes the post in TikTok.
- Historical maintenance branches are created from release tags only when a real backport is needed.
- Version-specific banner artwork was removed from the published documentation.

### Fixed

- Platform API success envelopes that contain an `error` object with an `ok` / `success` code are no longer treated as failures.
- Scheduled posts are claimed before execution to reduce duplicate publication risk.
- OAuth callback token metadata preserves expiry information where providers expose it.
- `larapost:doctor` now reports an enabled MCP server with a missing `laravel/mcp` runtime as a configuration failure.

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
