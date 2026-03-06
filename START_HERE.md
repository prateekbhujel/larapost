# Start Here

Use this package in 6 steps:

1. Install package

```bash
composer require prateekbhujel/larapost
php artisan larapost:install
```

2. Add platform credentials to `.env` (optional if you use dashboard settings)
3. Open `/larapost/dashboard` and connect a provider account with one-click OAuth.

4. Or connect from CLI if you prefer manual flow:

```bash
php artisan larapost:add-account facebook
```

5. Publish immediately or schedule via dashboard or `SocialMedia::post()`
6. Add cron for scheduled processing:

```cron
* * * * * php /path/to/artisan larapost:run-scheduled >> /dev/null 2>&1
```

For full setup, production guidance, and release details, read `README.md`.
