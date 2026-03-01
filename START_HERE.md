# Start Here

Use this package in 5 steps:

1. Install package

```bash
composer require prateekbhujel/larapost
php artisan larapost:install
```

2. Add platform credentials to `.env`
3. Connect at least one account:

```bash
php artisan larapost:add-account facebook
```

4. Publish immediately or schedule via `SocialMedia::post()`
5. Add cron for scheduled processing:

```cron
* * * * * php /path/to/artisan larapost:run-scheduled >> /dev/null 2>&1
```

For full setup, production guidance, and release details, read `README.md`.
