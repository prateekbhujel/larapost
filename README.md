# LaraPost

![LaraPost banner](./docs/assets/brand/larapost-banner.png)

[![CI](https://github.com/prateekbhujel/larapost/actions/workflows/ci.yml/badge.svg)](https://github.com/prateekbhujel/larapost/actions/workflows/ci.yml)
[![Packagist](https://img.shields.io/packagist/v/prateekbhujel/larapost.svg)](https://packagist.org/packages/prateekbhujel/larapost)
[![PHP](https://img.shields.io/badge/php-%5E8.1-777BB4.svg)](https://php.net)
[![Laravel](https://img.shields.io/badge/laravel-10%20%7C%2011%20%7C%2012%20%7C%2013-FF2D20.svg)](https://laravel.com)
[![License](https://img.shields.io/badge/license-MIT-111111.svg)](./LICENSE)

LaraPost is a Laravel package for publishing and scheduling content to Facebook Pages, Twitter / X, and LinkedIn from one API and one dashboard.

LaraPost supports publishing and scheduling for Facebook Pages, Twitter / X, and LinkedIn from one Laravel API and dashboard.

## Features

- One fluent API via `SocialSync\Facades\SocialMedia`
- Built-in dashboard at `/larapost/dashboard`
- OAuth connect flow for Facebook, Twitter / X, and LinkedIn
- Multi-Page Facebook sync from one Meta login
- Immediate publishing and scheduled publishing
- Bulk composer for different content across different accounts
- Retry and scheduled runner commands
- CI for PHP `8.1`, `8.2`, `8.3`, `8.4`, and `8.5`

## Support Matrix

| Platform | Connect | Publish | Notes |
| --- | --- | --- | --- |
| Facebook | OAuth to Facebook login | Page posts with text, image URL, and video URL | Facebook Pages only. Personal profile posting is not supported. |
| Twitter / X | OAuth 2.0 | Text posts | Your X app still needs write access plus billing or credits. The dashboard does not upload media to X. |
| LinkedIn | OAuth 2.0 | Member profile text posts | LinkedIn organization pages are not supported. Image upload expects a readable local file path when used programmatically. |


## Installation

```bash
composer require prateekbhujel/larapost
php artisan larapost:install
```

Manual setup:

```bash
php artisan vendor:publish --tag=larapost-config
php artisan vendor:publish --tag=larapost-migrations
php artisan vendor:publish --tag=larapost-views
php artisan migrate
```

## Configuration

```env
LARAPOST_DEFAULT_PLATFORM=facebook
LARAPOST_QUEUE_ENABLED=true
LARAPOST_MAX_RETRY_ATTEMPTS=3
LARAPOST_UI_ENABLED=true

FACEBOOK_APP_ID=
FACEBOOK_APP_SECRET=
FACEBOOK_API_VERSION=v20.0

TWITTER_CLIENT_ID=
TWITTER_CLIENT_SECRET=

LINKEDIN_CLIENT_ID=
LINKEDIN_CLIENT_SECRET=
```

Dashboard-saved provider credentials override `.env` values.

## Quick Start

Connect at least one provider account, then publish from code:

```php
use SocialSync\Facades\SocialMedia;

$results = SocialMedia::post()
    ->content('Release update from LaraPost')
    ->platforms(['facebook', 'twitter'])
    ->publish();
```

Schedule for later:

```php
use SocialSync\Facades\SocialMedia;

$results = SocialMedia::post()
    ->content('Tomorrow morning post')
    ->platforms(['facebook'])
    ->scheduleFor(now()->addHours(12))
    ->publish();
```

Connect an account from the CLI:

```bash
php artisan larapost:add-account facebook
php artisan larapost:add-account twitter
php artisan larapost:add-account linkedin
```

## Dashboard

The built-in dashboard lives at `GET /larapost/dashboard`.

It includes:

- Provider credential forms with encrypted database storage
- Login popups for Facebook Pages, Twitter / X, and LinkedIn profiles
- Account targeting for exact Pages and accounts
- Bulk composer for different copy per connected account
- Recent publish history and account toggles

The dashboard is a real operator surface, not a demo screen. It only exposes the support that ships in `v1.0.0`.

## Scheduling

Run the scheduled runner every minute:

```cron
* * * * * php /path/to/artisan larapost:run-scheduled >> /dev/null 2>&1
```

Useful commands:

- `php artisan larapost:install`
- `php artisan larapost:add-account {platform}`
- `php artisan larapost:test`
- `php artisan larapost:run-scheduled`

## Docs

- Docs portal: [https://prateekbhujel.github.io/larapost/](https://prateekbhujel.github.io/larapost/)
- Contributing guide: [CONTRIBUTING.md](./CONTRIBUTING.md)
- Security policy: [SECURITY.md](./SECURITY.md)
- Release playbook: [RELEASE.md](./RELEASE.md)
- Changelog: [CHANGELOG.md](./CHANGELOG.md)

## Contributing

Community contributions should come from forks. See [CONTRIBUTING.md](./CONTRIBUTING.md) for the expected workflow.

## License

LaraPost is released under the [MIT license](./LICENSE).
