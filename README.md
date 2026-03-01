# Laravel Social Sync

[![CI](https://github.com/prateekbhujel/laravel-social-sync/actions/workflows/ci.yml/badge.svg)](https://github.com/prateekbhujel/laravel-social-sync/actions/workflows/ci.yml)
[![License](https://img.shields.io/badge/license-MIT-green.svg)](LICENSE)
[![PHP](https://img.shields.io/badge/php-%5E8.1-777BB4.svg)](https://php.net)
[![Laravel](https://img.shields.io/badge/laravel-10%20%7C%2011%20%7C%2012%20%7C%2013-FF2D20.svg)](https://laravel.com)

Production-ready Laravel package for publishing and scheduling social content across Facebook, Instagram, Twitter/X, and LinkedIn using one API.

Supports Laravel `10 | 11 | 12 | 13` and PHP `8.1+`.

User documentation page (with light/dark mode): [GitHub Pages docs](https://prateekbhujel.github.io/laravel-social-sync/)

## Stability and Versions

- Stable channel: `^1.1` (recommended for production)
- Preview channel: `1.x-dev` (for testing upcoming changes)
- Current documented release: `v1.1.1`
- Package migration: use `prateekbhujel/laravel-social-sync` (replaces `socialsync/laravel-social-sync`)

## Why Teams Use It

- One fluent API (`SocialMedia::post()`) for all supported platforms
- Encrypted credential storage in your own database
- Immediate and scheduled publishing with retry/backoff
- OAuth connect flow for account onboarding
- Artisan commands for installation, testing, and scheduled processing
- Safe concurrent scheduled-runner behavior for multi-worker cron setups

## Installation

```bash
composer require prateekbhujel/laravel-social-sync
```

If you are migrating from the old vendor package:

```bash
composer remove socialsync/laravel-social-sync
composer require prateekbhujel/laravel-social-sync
```

Run installer:

```bash
php artisan social-sync:install
```

Manual setup:

```bash
php artisan vendor:publish --tag=social-sync-config
php artisan vendor:publish --tag=social-sync-migrations
php artisan migrate
```

## Environment Variables

```env
SOCIAL_SYNC_DEFAULT_PLATFORM=facebook

FACEBOOK_APP_ID=
FACEBOOK_APP_SECRET=
FACEBOOK_API_VERSION=v20.0

TWITTER_CLIENT_ID=
TWITTER_CLIENT_SECRET=

LINKEDIN_CLIENT_ID=
LINKEDIN_CLIENT_SECRET=
```

Instagram falls back to Facebook credentials unless dedicated `INSTAGRAM_*` values are set.

## Quick Start

### Publish Now

```php
use SocialSync\Facades\SocialMedia;

$results = SocialMedia::post()
    ->content('New update from our Laravel app')
    ->platforms(['facebook', 'linkedin'])
    ->publish();
```

### Schedule a Post

```php
SocialMedia::post()
    ->content('Scheduled campaign message')
    ->platforms(['facebook', 'twitter'])
    ->scheduleFor(now()->addHours(2))
    ->publish();
```

Run scheduled posts via cron:

```bash
php artisan social-sync:run-scheduled
```

Example cron:

```cron
* * * * * php /path/to/artisan social-sync:run-scheduled >> /dev/null 2>&1
```

### Post Media

```php
SocialMedia::post()
    ->content('Image post')
    ->image('https://cdn.example.com/image.jpg')
    ->platforms(['instagram'])
    ->publish();
```

## Account Management

CLI flow:

```bash
php artisan social-sync:add-account facebook
```

Web flow endpoints:

- `GET /social-sync/connect/{platform}`
- `GET /social-sync/callback/{platform}`

Route prefix and middleware are configurable in `config/social-sync.php`.

## Artisan Commands

- `php artisan social-sync:install`
- `php artisan social-sync:add-account {platform}`
- `php artisan social-sync:test`
- `php artisan social-sync:run-scheduled`

## Data Model

- `social_accounts`: account metadata + encrypted credentials
- `scheduled_posts`: scheduling state, retries, and publish responses

## Testing

```bash
composer test
```

## Production Checklist

- Configure all required OAuth credentials in `.env`
- Run migrations in production before enabling scheduled jobs
- Add the scheduled runner to cron (`social-sync:run-scheduled`)
- Monitor failed posts and retry behavior
- Keep package on latest `1.x` patch release

## Contributing

Contributions are welcome. Read [CONTRIBUTING.md](CONTRIBUTING.md) for setup, branching, testing, and PR standards.

## Security

Report vulnerabilities privately via [SECURITY.md](SECURITY.md).

## Release Process

Release guidance is documented in [CHANGELOG.md](CHANGELOG.md) and [RELEASE.md](RELEASE.md).

## License

MIT
