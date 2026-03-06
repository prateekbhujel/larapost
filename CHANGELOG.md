# Changelog

All notable changes to this project are documented in this file.

## [Unreleased]

### Added

- No unreleased changes yet.

## [2.1.0] - 2026-03-06

### Added

- Full LaraPost web dashboard at `/larapost/dashboard` with:
  - provider connect buttons (Facebook, Instagram, Twitter/X, LinkedIn)
  - manual provider credential forms
  - publish-now and schedule UI
  - account enable/disable controls
  - recent post history view
- Encrypted database-backed provider credential storage via `larapost_platform_credentials`
- View publishing support with `larapost-views` tag
- Feature tests for dashboard rendering, credential storage, and publish flow

### Changed

- OAuth callback flow now redirects back to dashboard with success/error flash messages
- `larapost:install` now publishes package views in addition to config and migrations
- Composer branch alias updated from `1.x-dev` to `2.x-dev`
- README and docs updated for the dashboard-first UX and manual credential workflow

## [1.1.1] - 2026-03-01

### Added

- Contributor-facing project docs: `CONTRIBUTING.md`, `SECURITY.md`, and `CODE_OF_CONDUCT.md`
- CI workflow and issue/PR templates for reliable open-source collaboration
- Release playbook (`RELEASE.md`) for repeatable tagging and publishing

### Changed

- Package vendor updated to `prateekbhujel/larapost`
- README refreshed with stability channels and production checklist
- Setup scripts now use `packages/prateekbhujel/larapost`

### Fixed

- Scheduled post runner now atomically claims posts before processing to avoid duplicate publishing under concurrent workers
- `larapost:run-scheduled` now treats non-positive `--limit` values safely as `1`
