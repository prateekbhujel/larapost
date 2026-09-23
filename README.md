# LaraPost

[![CI](https://github.com/prateekbhujel/larapost/actions/workflows/ci.yml/badge.svg)](https://github.com/prateekbhujel/larapost/actions/workflows/ci.yml)
[![Packagist](https://img.shields.io/packagist/v/prateekbhujel/larapost.svg)](https://packagist.org/packages/prateekbhujel/larapost)
[![PHP](https://img.shields.io/badge/PHP-8.3%2B-777BB4.svg)](https://php.net)
[![Laravel](https://img.shields.io/badge/Laravel-12%20%7C%2013-FF2D20.svg)](https://laravel.com)
[![License](https://img.shields.io/badge/license-MIT-111111.svg)](./LICENSE)

LaraPost is Laravel-native social publishing infrastructure for applications that need to connect accounts, publish or schedule content, run work through Laravel queues, and optionally expose the same workflow to MCP clients such as ChatGPT, Claude, Codex, or another compatible client.

It is not a separate social-media SaaS. It lives inside your Laravel application, so your app owns the database records, business rules, connected accounts, publishing history, and provider credentials.

## What ships in 2.x

- Facebook Pages
- Twitter / X with OAuth 2.0 plus the optional Xquik backend
- LinkedIn member profiles
- TikTok Content Posting API with safe upload-to-inbox by default and explicit Direct Post opt-in
- Immediate, scheduled, and queued publishing
- Multi-account targeting
- Retry and backoff for scheduled work
- Token-expiry tracking and refresh where the provider supports it
- Authenticated operator dashboard
- Custom driver registration
- Optional remote MCP server for AI clients
- A diagnostic command with `php artisan larapost:doctor`

## Requirements

LaraPost 2.x targets:

- PHP 8.3+
- Laravel 12 or 13

Laravel 11 is no longer in its security support window, so LaraPost 2.x does not carry that compatibility burden. Old maintenance branches are created from release tags only when a real backport is needed.

## Installation

```bash
composer require prateekbhujel/larapost
php artisan larapost:install
```

The installer asks which platforms you want. You can enable one provider, several, or all of them.

Non-interactive examples:

```bash
# Facebook only
php artisan larapost:install --platforms=facebook

# Facebook + TikTok
php artisan larapost:install --platforms=facebook,tiktok

# API/package usage without the built-in dashboard
php artisan larapost:install --platforms=linkedin --no-dashboard

# Prepare the optional MCP integration too
php artisan larapost:install --platforms=facebook,tiktok --mcp
```

Enabled platforms are controlled by:

```env
LARAPOST_PLATFORMS=facebook,tiktok
```

Disabled drivers do not appear in the manager, dashboard, or MCP capability list.

## Publish

```php
use SocialSync\Facades\SocialMedia;

$results = SocialMedia::post()
    ->content('We just shipped a new release.')
    ->platform('facebook')
    ->publish();
```

Target exact connected accounts:

```php
SocialMedia::post()
    ->content('Only these destinations')
    ->platform('facebook')
    ->accounts([
        'facebook' => [12, 19],
    ])
    ->publish();
```

Schedule for later:

```php
SocialMedia::post()
    ->content('Tomorrow morning')
    ->platform('linkedin')
    ->scheduleFor(now()->addDay()->setTime(9, 0))
    ->publish();
```

Queue immediate work:

```php
SocialMedia::post()
    ->content('Process this through Laravel queues')
    ->platform('facebook')
    ->queue();
```

## TikTok

LaraPost defaults to TikTok's upload flow:

```env
TIKTOK_PUBLISH_MODE=upload
```

In upload mode, LaraPost sends the photo or video to the creator's TikTok inbox and the creator reviews and completes the post in TikTok. This is the safe default for the built-in dashboard, automation, and MCP clients.

```php
SocialMedia::post()
    ->content('Behind the scenes from launch day')
    ->video('https://media.example.com/launch.mp4')
    ->platform('tiktok')
    ->publish();
```

Media uses `PULL_FROM_URL`, so it must be available over HTTPS from a domain or URL prefix verified for the TikTok developer app.

Direct Post is available only as an explicit application-level opt-in with `TIKTOK_PUBLISH_MODE=direct`. In that mode LaraPost requires current creator info, an explicit creator-selected privacy level, explicit interaction settings, and `metadata.tiktok.consent=true`. The built-in MCP tool intentionally refuses TikTok Direct Post. A host application using Direct Post must implement TikTok's required creator-facing sharing UX and pass those choices to LaraPost.

Changing TikTok publish mode changes the OAuth scope, so reconnect existing TikTok accounts after changing between `upload` and `direct`.

## Optional MCP for ChatGPT, Claude, Codex, and other clients

MCP is not required for normal LaraPost installations.

Install it only when you want an AI client to interact with LaraPost:

```bash
composer require laravel/mcp:^1.0
php artisan larapost:install --mcp
```

Then configure:

```env
LARAPOST_MCP_ENABLED=true
LARAPOST_MCP_PATH=/larapost/mcp
LARAPOST_MCP_TOKEN=replace-with-a-long-random-secret

LARAPOST_BUSINESS_NAME="Acme"
LARAPOST_BUSINESS_DESCRIPTION="What the company actually does"
LARAPOST_TARGET_AUDIENCE="Who the content is for"
LARAPOST_BRAND_VOICE="Clear, practical, no hype"
LARAPOST_CONTENT_GUIDELINES="Never invent prices or guarantees"
```

The MCP server exposes read-only business/account/history tools plus explicit write tools for publishing and cancelling pending posts. Provider tokens and account credentials are never returned by read tools.

Use the remote server URL:

```text
https://your-app.example/larapost/mcp
```

Authentication uses:

```http
Authorization: Bearer <LARAPOST_MCP_TOKEN>
```

For MCP clients that require OAuth rather than a static bearer token, integrate LaraPost with your application's Laravel MCP OAuth/authorization layer instead of exposing the endpoint anonymously.

## Dashboard security

The dashboard is an operator surface, not a demo route. It can publish content and change provider credentials.

LaraPost 2.x protects operator routes with:

```php
['web', 'auth']
```

by default. If your application uses another guard, tenant middleware, or admin authorization layer, override `operator_middleware` in the published config.

OAuth callbacks use a separate callback middleware stack so provider redirects do not inherit an application-specific operator policy accidentally.

## Scheduler and queues

LaraPost registers its scheduled runner with Laravel's scheduler automatically.

Your application still needs Laravel's scheduler process:

```bash
php artisan schedule:work
```

or the normal `schedule:run` cron entry.

Enable queued scheduled publishing with:

```env
LARAPOST_QUEUE_ENABLED=true
LARAPOST_QUEUE_CONNECTION=redis
LARAPOST_QUEUE_NAME=larapost
```

Due posts are claimed before publishing, and queued publishing uses a unique job per scheduled post.

## Custom drivers

Register another implementation without editing package internals:

```php
use SocialSync\Facades\SocialMedia;

SocialMedia::extend('mastodon', App\Social\MastodonDriver::class);
```

Custom drivers implement `SocialSync\Contracts\SocialDriverInterface`.

## Diagnostics

Before production rollout:

```bash
php artisan larapost:doctor
```

The command checks database tables, enabled platforms, scheduler status, queue mode, dashboard access middleware, and MCP configuration.

## Documentation

Full documentation:

https://prateekbhujel.github.io/larapost/

Start here:

- [Installation](https://prateekbhujel.github.io/larapost/getting-started/)
- [Configuration](https://prateekbhujel.github.io/larapost/configuration/)
- [Platforms](https://prateekbhujel.github.io/larapost/platforms/)
- [Publishing & scheduling](https://prateekbhujel.github.io/larapost/publishing/)
- [AI & MCP](https://prateekbhujel.github.io/larapost/ai-mcp/)
- [Upgrade from 1.x](https://prateekbhujel.github.io/larapost/upgrade/)

Repository docs:

- [UPGRADE.md](./UPGRADE.md)
- [BACKPORTING.md](./BACKPORTING.md)
- [CONTRIBUTING.md](./CONTRIBUTING.md)
- [SECURITY.md](./SECURITY.md)
- [RELEASE.md](./RELEASE.md)
- [CHANGELOG.md](./CHANGELOG.md)

## License

LaraPost is released under the [MIT license](./LICENSE).
