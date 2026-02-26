# Start Here

Use this package in 4 steps:

1. Install package

```bash
composer require socialsync/laravel-social-sync
php artisan social-sync:install
```

2. Add credentials to `.env` (see `README.md`)
3. Connect at least one account (`php artisan social-sync:add-account facebook`)
4. Publish or schedule via `SocialMedia::post()` API

For full setup and release guidance, read `README.md`.
