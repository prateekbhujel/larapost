# LaraPost

[![CI](https://github.com/prateekbhujel/larapost/actions/workflows/ci.yml/badge.svg)](https://github.com/prateekbhujel/larapost/actions/workflows/ci.yml)
[![License](https://img.shields.io/badge/license-MIT-green.svg)](LICENSE)
[![PHP](https://img.shields.io/badge/php-%5E8.1-777BB4.svg)](https://php.net)
[![Laravel](https://img.shields.io/badge/laravel-10%20%7C%2011%20%7C%2012%20%7C%2013-FF2D20.svg)](https://laravel.com)

LaraPost is a production-focused Laravel package for publishing and scheduling social content across Facebook, Instagram, Twitter/X, and LinkedIn from one API.

## Table of Contents

- [Why LaraPost](#why-larapost)
- [Compatibility](#compatibility)
- [Installation](#installation)
- [Quick Start](#quick-start)
- [Web Dashboard](#web-dashboard)
- [Configuration](#configuration)
- [OAuth Routes](#oauth-routes)
- [Artisan Commands](#artisan-commands)
- [Documentation](#documentation)
- [Contributing](#contributing)
- [Security](#security)
- [Release Process](#release-process)
- [License](#license)

## Why LaraPost

- Single fluent API for multi-platform publishing (`SocialMedia::post()`)
- Account onboarding via OAuth
- Optional web dashboard with one-click connect buttons and publish/schedule form
- Manual provider credential forms in UI (with `.env` fallback)
- Retry/backoff handling for transient failures
- Command tooling for install, testing, and scheduled processing

## Compatibility

- PHP: `8.1+`
- Laravel: `10`, `11`, `12`, `13`
- Package: `prateekbhujel/larapost`
- Stable release target: `v2.1.0`
- Branch alias: `2.x-dev`

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

## Quick Start

1. Configure provider credentials in `.env` **or** in dashboard UI.
2. Open `/larapost/dashboard` to connect providers with one-click OAuth.
3. Connect at least one social account.
4. Publish immediately or schedule content.
5. Run scheduled processing via cron.

### Connect an Account (CLI)

```bash
php artisan larapost:add-account facebook
```

### Publish Immediately (API)

```php
use SocialSync\Facades\SocialMedia;

$results = SocialMedia::post()
    ->content('New update from our Laravel app')
    ->platforms(['facebook', 'linkedin'])
    ->publish();
```

### Schedule a Post (API)

```php
SocialMedia::post()
    ->content('Scheduled campaign message')
    ->platforms(['facebook', 'twitter'])
    ->scheduleFor(now()->addHours(2))
    ->publish();
```

### Scheduler Cron

```cron
* * * * * php /path/to/artisan larapost:run-scheduled >> /dev/null 2>&1
```

## Web Dashboard

LaraPost includes a built-in dashboard:

- `GET /larapost/dashboard`

Dashboard features:

- Connect buttons for Facebook, Instagram, Twitter/X, and LinkedIn
- Provider app credential forms (saved in encrypted DB storage)
- Connected account enable/disable toggles
- Publish now / schedule form with optional media URL
- Recent post history panel

The UI can be disabled with:

```env
LARAPOST_UI_ENABLED=false
```

## Configuration

Key environment variables:

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

Configuration file: `config/larapost.php`

Credential precedence:

1. Dashboard-saved provider credentials (DB)
2. `.env` / config values

## OAuth Routes

Default routes:

- `GET /larapost/connect/{platform}`
- `GET /larapost/callback/{platform}`

Route prefix and middleware are configurable in `config/larapost.php`.

## Artisan Commands

- `php artisan larapost:install`
- `php artisan larapost:add-account {platform}`
- `php artisan larapost:test`
- `php artisan larapost:run-scheduled`

## Documentation

- Docs portal: [https://prateekbhujel.github.io/larapost/](https://prateekbhujel.github.io/larapost/)
- Contributing guide: [CONTRIBUTING.md](CONTRIBUTING.md)
- Security policy: [SECURITY.md](SECURITY.md)
- Release playbook: [RELEASE.md](RELEASE.md)
- Changelog: [CHANGELOG.md](CHANGELOG.md)

## Contributing

Contributions are welcome. Please read [CONTRIBUTING.md](CONTRIBUTING.md) before opening a pull request.

## Security

Please report vulnerabilities privately as described in [SECURITY.md](SECURITY.md).

## Release Process

Release and tagging workflow is documented in [RELEASE.md](RELEASE.md).

## License

LaraPost is open-sourced under the [MIT license](LICENSE).
