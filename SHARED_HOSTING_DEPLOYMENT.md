# Shared Hosting Notes

Laravel Social Sync works on shared hosting if your app can run Artisan commands.

## Required cron jobs

```cron
* * * * * php /path/to/artisan social-sync:run-scheduled >> /dev/null 2>&1
* * * * * php /path/to/artisan schedule:run >> /dev/null 2>&1
```

## Queue

If you enable queues in your app, configure your normal Laravel queue worker based on host support.

## Storage permissions

Ensure `storage/` and `bootstrap/cache/` are writable.

## Deployment checklist

- Run `php artisan migrate --force`
- Confirm OAuth credentials exist in production `.env`
- Verify cron is active and running every minute
- Test one publish flow after deploy
