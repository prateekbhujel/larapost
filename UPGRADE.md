# Upgrading LaraPost

## Upgrading from 1.x to 2.x

LaraPost 2.x is a major release because it changes runtime support and security defaults.

### Requirements

- PHP 8.3+
- Laravel 12 or 13

Laravel 11 is retained only on the `1.x` branch for critical backports.

### Update Composer

```bash
composer require prateekbhujel/larapost:^2.0 -W
```

### Review configuration

Republish the package config or compare it with your existing copy:

```bash
php artisan vendor:publish --tag=larapost-config --force
```

Important additions include:

- `enabled_platforms`
- scheduler settings
- separate `operator_middleware` and `callback_middleware`
- TikTok provider settings
- optional MCP settings

### Dashboard authorization

The v2 operator surface uses `['web', 'auth']` by default.

If your application uses a different guard, tenant middleware, or admin authorization layer, configure `operator_middleware` explicitly. Do not remove access control simply to preserve the old public route behavior.

### Choose only the platforms you use

```env
LARAPOST_PLATFORMS=facebook,tiktok
```

### Optional MCP

Normal publishing does not require Laravel MCP.

Only if you want ChatGPT, Claude, Codex, or another compatible MCP client:

```bash
composer require laravel/mcp:^1.0
```

Then enable:

```env
LARAPOST_MCP_ENABLED=true
LARAPOST_MCP_TOKEN=replace-with-a-long-random-secret
```

### Verify after upgrading

```bash
php artisan migrate
php artisan larapost:doctor
composer test
```
