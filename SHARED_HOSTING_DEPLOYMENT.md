# Shared Hosting Notes

Laravel Social Sync works on shared hosting if the app can run Artisan commands.

## Required cron jobs

```cron
* * * * * php /path/to/artisan social-sync:run-scheduled >> /dev/null 2>&1
* * * * * php /path/to/artisan schedule:run >> /dev/null 2>&1
```

## Queue

If you enable queues in your app, configure your standard Laravel queue worker according to your host support.

## Storage permissions

Ensure `storage/` and `bootstrap/cache/` are writable.
