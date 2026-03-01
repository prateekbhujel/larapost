# Changelog

All notable changes to this project are documented in this file.

## [1.0.1] - 2026-03-01

### Added

- Contributor-facing project docs: `CONTRIBUTING.md`, `SECURITY.md`, and `CODE_OF_CONDUCT.md`
- CI workflow and issue/PR templates for reliable open-source collaboration
- Release playbook (`RELEASE.md`) for repeatable tagging and publishing

### Changed

- Package vendor updated to `prateekbhujel/laravel-social-sync`
- README refreshed with stability channels and production checklist
- Setup scripts now use `packages/prateekbhujel/laravel-social-sync`

### Fixed

- Scheduled post runner now atomically claims posts before processing to avoid duplicate publishing under concurrent workers
- `social-sync:run-scheduled` now treats non-positive `--limit` values safely as `1`
