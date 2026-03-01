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
- Immediate and scheduled posting workflows
- Retry/backoff handling for transient failures
- Command tooling for install, testing, and scheduled processing
- Safe scheduled-runner behavior for concurrent workers

## Compatibility

- PHP: `8.1+`
- Laravel: `10`, `11`, `12`, `13`
- Package: `prateekbhujel/larapost`

## Installation

```bash
composer require prateekbhujel/larapost
php artisan larapost:install
```

Manual setup:

```bash
php artisan vendor:publish --tag=larapost-config
php artisan vendor:publish --tag=larapost-migrations
php artisan migrate
```

## Quick Start

1. Add provider credentials to `.env`.
2. Connect at least one social account.
3. Publish immediately or schedule content.
4. Run scheduled processing via cron.

### Connect an Account

```bash
php artisan larapost:add-account facebook
```

### Publish Immediately

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

### Post Media

```php
SocialMedia::post()
    ->content('Image post')
    ->image('https://cdn.example.com/image.jpg')
    ->platforms(['instagram'])
    ->publish();
```

### Scheduler Cron

```cron
* * * * * php /path/to/artisan larapost:run-scheduled >> /dev/null 2>&1
```

## Configuration

Key environment variables:

```env
LARAPOST_DEFAULT_PLATFORM=facebook
LARAPOST_QUEUE_ENABLED=true
LARAPOST_MAX_RETRY_ATTEMPTS=3

FACEBOOK_APP_ID=
FACEBOOK_APP_SECRET=
FACEBOOK_API_VERSION=v20.0

TWITTER_CLIENT_ID=
TWITTER_CLIENT_SECRET=

LINKEDIN_CLIENT_ID=
LINKEDIN_CLIENT_SECRET=
```

Configuration file: `config/larapost.php`

Instagram credentials fall back to Facebook values unless `INSTAGRAM_*` is explicitly set.

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
