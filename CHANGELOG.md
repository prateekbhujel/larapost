# Changelog

All notable changes to this project are documented in this file.

## [Unreleased]

### Added

- Laravel-inspired production documentation portal layout for GitHub Pages with richer operational guidance

### Changed

- Compatibility constraints expanded to include `illuminate/support` for Laravel 12 and 13
- Dev test matrix dependencies expanded for newer Laravel/Testbench lines
- Package now explicitly replaces `socialsync/laravel-social-sync` for smoother vendor migration

## [1.1.1] - 2026-03-01

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
