# Laravel Social Sync

[![License](https://img.shields.io/badge/license-MIT-green.svg)](LICENSE)
[![PHP](https://img.shields.io/badge/php-%5E8.1-777BB4.svg)](https://php.net)
[![Laravel](https://img.shields.io/badge/laravel-10%20%7C%2011-FF2D20.svg)](https://laravel.com)

Laravel package for publishing to multiple social platforms with one API, account storage, and scheduled posting.

## What It Includes

- Unified `SocialMedia::post()` API
- Platform drivers: Facebook, Instagram, Twitter/X, LinkedIn
- Encrypted credential storage (`social_accounts` table)
- Immediate or scheduled posting (`scheduled_posts` table)
- Retry strategy for scheduled posts
- Artisan commands for install, account connect, test post, and scheduled runner

## Installation

```bash
composer require socialsync/laravel-social-sync
```

Run installer:

```bash
php artisan social-sync:install
```

If you prefer manual setup:

```bash
php artisan vendor:publish --tag=social-sync-config
php artisan vendor:publish --tag=social-sync-migrations
php artisan migrate
```

## Environment Variables

Add platform credentials in `.env`:

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

Instagram defaults to Facebook app credentials unless you set dedicated `INSTAGRAM_*` values.

## Quick Usage

### Publish now

```php
use SocialSync\Facades\SocialMedia;

$results = SocialMedia::post()
    ->content('New update from our Laravel app')
    ->platforms(['facebook', 'linkedin'])
    ->publish();
```

### Schedule

```php
SocialMedia::post()
    ->content('Scheduled campaign message')
    ->platforms(['facebook', 'twitter'])
    ->scheduleFor(now()->addHours(2))
    ->publish();
```

Run scheduled posts (via cron):

```bash
php artisan social-sync:run-scheduled
```

Example cron:

```cron
* * * * * php /path/to/artisan social-sync:run-scheduled >> /dev/null 2>&1
```

### Media

```php
SocialMedia::post()
    ->content('Image post')
    ->image('https://cdn.example.com/image.jpg')
    ->platforms(['instagram'])
    ->publish();
```

## Account Management

Connect account using CLI flow:

```bash
php artisan social-sync:add-account facebook
```

Or web flow endpoints:

- `GET /social-sync/connect/{platform}`
- `GET /social-sync/callback/{platform}`

You can customize route prefix/middleware in `config/social-sync.php`.

## Commands

- `php artisan social-sync:install`
- `php artisan social-sync:add-account {platform}`
- `php artisan social-sync:test`
- `php artisan social-sync:run-scheduled`

## Data Model

Two tables are created:

- `social_accounts`: account metadata + encrypted credentials
- `scheduled_posts`: queue/schedule state, retry counters, publish responses

## Testing

```bash
composer test
```

The package includes Testbench coverage for publish flow, scheduling, and scheduled command execution.

## Versioning and Releases

Use semantic versioning:

- `MAJOR`: breaking API/config changes
- `MINOR`: backward-compatible features
- `PATCH`: backward-compatible fixes

Release flow:

```bash
git tag v1.1.0
git push origin v1.1.0
```

Packagist auto-updates when your webhook is configured. You can also trigger an update manually from Packagist.

## License

MIT
