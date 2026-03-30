# Changelog

All notable changes to LaraPost are documented in this file.

## [Unreleased]

- No unreleased changes yet.

## [1.0.0] - 2026-03-30

### Added

- Stable package branding, docs assets, and refreshed GitHub-facing README
- Bulk composer for publishing different content across different connected accounts
- Multi-Page Facebook sync from one OAuth login
- CI coverage for PHP `8.1`, `8.2`, `8.3`, `8.4`, and `8.5`

### Changed

- Stable support surface is now explicitly limited to Facebook Pages, Twitter / X, and LinkedIn member profiles
- Instagram has been removed from the published stable scope and docs
- Dashboard copy now reflects provider-specific limits instead of promising unsupported flows
- Package metadata now targets the `v1.0.0` stable release line

### Fixed

- Facebook publishing now uses the correct Page access token for Page posts
- Facebook OAuth URL generation normalizes Meta API versions correctly
- Twitter confidential-client OAuth token exchange now sends client credentials correctly
