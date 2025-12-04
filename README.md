# 🚀 Social Sync for Laravel

[![Latest Version](https://img.shields.io/badge/version-1.0.0-blue.svg)](https://github.com/socialsync/laravel-social-sync)
[![PHP Version](https://img.shields.io/badge/php-%3E%3D8.1-8892BF.svg)](https://php.net/)
[![Laravel Version](https://img.shields.io/badge/laravel-10.x%20%7C%2011.x-FF2D20.svg)](https://laravel.com/)
[![License](https://img.shields.io/badge/license-MIT-green.svg)](LICENSE)

> **Post to multiple social media platforms with a single, elegant API.** Write once, post everywhere.

Social Sync is a powerful Laravel package that simplifies social media posting across multiple platforms. Perfect for CRM applications, marketing automation, content management systems, or any application that needs to publish content to social media.

---

## ✨ Features

- 🎯 **Unified API** - One simple interface for all platforms
- 🔄 **Multi-Platform Support** - Facebook, Instagram, Twitter/X, LinkedIn
- 📅 **Smart Scheduling** - Queue posts for optimal times
- 🔐 **Secure** - Encrypted credential storage
- 🚀 **Queue Support** - Background processing with retries
- 📊 **Multi-Account** - Manage multiple accounts per platform
- 🖼️ **Media Support** - Images, videos, and carousels
- 🔄 **Auto-Retry** - Failed posts automatically retry
- 📱 **Webhook Support** - Receive platform updates
- 🌐 **Shared Hosting Ready** - Works everywhere Laravel runs

---

## 📋 Requirements

- PHP 8.1 or higher
- Laravel 10.x or 11.x
- MySQL, PostgreSQL, or SQLite
- Composer

---

## 📦 Installation

```bash
composer require socialsync/laravel-social-sync
```

### Publish Configuration

```bash
php artisan social-sync:install
```

This will:
- Publish configuration file
- Run migrations
- Create storage directories
- Show required environment variables

### Configure Environment

Add to your `.env`:

```env
# Facebook
FACEBOOK_APP_ID=your_app_id
FACEBOOK_APP_SECRET=your_app_secret

# Instagram (uses Facebook app)
INSTAGRAM_APP_ID=your_app_id
INSTAGRAM_APP_SECRET=your_app_secret

# Twitter/X
TWITTER_API_KEY=your_api_key
TWITTER_API_SECRET=your_api_secret
TWITTER_BEARER_TOKEN=your_bearer_token

# LinkedIn
LINKEDIN_CLIENT_ID=your_client_id
LINKEDIN_CLIENT_SECRET=your_client_secret

# Queue
QUEUE_CONNECTION=database
```

### Setup Queue

```bash
php artisan queue:table
php artisan migrate
```

Run queue worker:

```bash
php artisan queue:work
```

---

## 🚀 Quick Start

### Connect Your First Account

```bash
php artisan social-sync:add-account facebook
```

### Post Immediately

```php
use SocialSync\Facades\SocialMedia;

SocialMedia::post()
    ->content('Hello from Social Sync! 🚀')
    ->platforms(['facebook', 'instagram'])
    ->publish();
```

### Schedule a Post

```php
SocialMedia::post()
    ->content('Weekend sale - 50% off!')
    ->image('path/to/image.jpg')
    ->scheduleFor('2024-12-07 09:00:00')
    ->platforms(['facebook', 'instagram', 'twitter'])
    ->publish();
```

### Post to Specific Accounts

```php
SocialMedia::post()
    ->content('Company announcement')
    ->platforms(['facebook', 'linkedin'])
    ->accounts([
        'facebook' => [1, 3, 5],  // Specific account IDs
        'linkedin' => 'all'       // All active accounts
    ])
    ->publish();
```

---

## 📖 Full Usage Guide

### Posting Content

#### Basic Post

```php
use SocialSync\Facades\SocialMedia;

$results = SocialMedia::post()
    ->content('Your content here')
    ->platforms(['facebook'])
    ->publish();
```

#### Post with Image

```php
SocialMedia::post()
    ->content('Check out this amazing photo!')
    ->image('storage/images/photo.jpg')
    ->platforms(['facebook', 'instagram'])
    ->publish();
```

#### Multiple Images (Carousel)

```php
SocialMedia::post()
    ->content('Our product gallery')
    ->image([
        'storage/gallery/1.jpg',
        'storage/gallery/2.jpg',
        'storage/gallery/3.jpg',
    ])
    ->platforms(['facebook', 'instagram'])
    ->publish();
```

#### Post with Video

```php
SocialMedia::post()
    ->content('Our new product video')
    ->video('storage/videos/promo.mp4')
    ->platforms(['facebook', 'twitter'])
    ->publish();
```

#### Scheduled Post

```php
use Carbon\Carbon;

SocialMedia::post()
    ->content('Scheduled content')
    ->scheduleFor(Carbon::tomorrow()->setTime(9, 0))
    ->platforms(['facebook', 'twitter'])
    ->publish();
```

#### With Metadata

```php
SocialMedia::post()
    ->content('Campaign post')
    ->metadata([
        'campaign_id' => 123,
        'utm_source' => 'social',
        'custom_field' => 'value'
    ])
    ->platforms(['facebook'])
    ->publish();
```

### Managing Accounts

#### Connect Account via CLI

```bash
# Connect new account
php artisan social-sync:add-account facebook

# List all accounts
php artisan social-sync:list-accounts

# Filter by platform
php artisan social-sync:list-accounts --platform=instagram
```

#### Connect Account via Web

```php
// In your route
Route::get('/connect/{platform}', function ($platform) {
    return redirect()->route('social-sync.connect', ['platform' => $platform]);
});
```

Then visit: `https://yourapp.com/connect/facebook`

#### Access Accounts in Code

```php
use SocialSync\Models\SocialAccount;

// Get all active accounts
$accounts = SocialAccount::active()->get();

// Get accounts for specific platform
$fbAccounts = SocialAccount::platform('facebook')->active()->get();

// Get specific account
$account = SocialAccount::find(1);
```

### Checking Post Status

```php
use SocialSync\Models\ScheduledPost;

// Get pending posts
$pending = ScheduledPost::pending()->get();

// Get published posts
$published = ScheduledPost::published()->get();

// Get failed posts
$failed = ScheduledPost::failed()->get();

// Get posts scheduled before now
$ready = ScheduledPost::pending()
    ->scheduledBefore(now())
    ->get();
```

---

## 🎨 Integration Examples

### CRM Integration

```php
// In your Lead controller
use App\Models\Lead;
use SocialSync\Facades\SocialMedia;

public function store(Request $request)
{
    $lead = Lead::create($request->validated());

    // Auto-post to social media
    SocialMedia::post()
        ->content("New lead: {$lead->name} 🎉")
        ->platforms(['facebook', 'linkedin'])
        ->publish();

    return redirect()->back();
}
```

### E-commerce Integration

```php
// When product is created
use App\Models\Product;
use SocialSync\Facades\SocialMedia;

public function announceProduct(Product $product)
{
    SocialMedia::post()
        ->content("New product: {$product->name}\n\n{$product->description}")
        ->image($product->image_path)
        ->platforms(['facebook', 'instagram', 'twitter'])
        ->scheduleFor($product->launch_date)
        ->metadata(['product_id' => $product->id])
        ->publish();
}
```

### Blog Integration

```php
// Auto-share new blog posts
use App\Models\Post;
use SocialSync\Facades\SocialMedia;

public function publishPost(Post $post)
{
    $post->update(['published_at' => now()]);

    SocialMedia::post()
        ->content("{$post->title}\n\n{$post->excerpt}\n\nRead more: {$post->url}")
        ->image($post->featured_image)
        ->platforms(['facebook', 'twitter', 'linkedin'])
        ->publish();
}
```

---

## 🎭 Events

Social Sync dispatches events you can listen to:

```php
// In EventServiceProvider
protected $listen = [
    \SocialSync\Events\PostPublished::class => [
        \App\Listeners\SendSuccessNotification::class,
    ],
    \SocialSync\Events\PostFailed::class => [
        \App\Listeners\AlertAdministrator::class,
    ],
];
```

### PostPublished Event

```php
namespace App\Listeners;

use SocialSync\Events\PostPublished;

class SendSuccessNotification
{
    public function handle(PostPublished $event)
    {
        $post = $event->scheduledPost;
        $result = $event->result;

        // Send notification, log, etc.
    }
}
```

### PostFailed Event

```php
namespace App\Listeners;

use SocialSync\Events\PostFailed;

class AlertAdministrator
{
    public function handle(PostFailed $event)
    {
        $post = $event->scheduledPost;
        $error = $event->errorMessage;

        // Send alert, log error, etc.
    }
}
```

---

## ⚙️ Configuration

### Platforms

Configure in `config/social-sync.php`:

```php
'platforms' => [
    'facebook' => [
        'app_id' => env('FACEBOOK_APP_ID'),
        'app_secret' => env('FACEBOOK_APP_SECRET'),
    ],
    // ... other platforms
],
```

### Queue Settings

```php
'queue' => [
    'enabled' => true,
    'connection' => 'database',
    'queue_name' => 'social-posts',
],
```

### Retry Logic

```php
'retry' => [
    'max_attempts' => 3,
    'backoff_minutes' => [1, 5, 15],
],
```

### Rate Limits

```php
'rate_limits' => [
    'facebook' => [
        'posts_per_hour' => 30,
        'posts_per_day' => 200,
    ],
    // ... other platforms
],
```

---

## 🧪 Testing

```bash
# Test posting via CLI
php artisan social-sync:test

# Test specific platform
php artisan social-sync:test --platform=facebook

# Test with custom content
php artisan social-sync:test --content="Test post"
```

---

## 🚀 Deployment

### Shared Hosting

See [SHARED_HOSTING_DEPLOYMENT.md](SHARED_HOSTING_DEPLOYMENT.md) for detailed guide.

Quick setup:
1. Upload via FTP
2. Configure `.env`
3. Run migrations
4. Setup cron:
```bash
* * * * * php /path/to/artisan schedule:run
*/5 * * * * php /path/to/artisan queue:work --stop-when-empty
```

### VPS/Dedicated

Use Supervisor for queue:

```ini
[program:laravel-queue]
command=php /path/to/artisan queue:work --sleep=3 --tries=3
autostart=true
autorestart=true
user=www-data
```

---

## 📚 Documentation

- [Complete Installation Guide](COMPLETE_INSTALLATION_GUIDE.md)
- [Quick Start (30 min)](QUICK_START_GUIDE.md)
- [Project Structure](PROJECT_STRUCTURE.md)
- [Shared Hosting Deployment](SHARED_HOSTING_DEPLOYMENT.md)

---

## 🔒 Security

- Credentials stored encrypted
- OAuth 2.0 authentication
- CSRF protection on callbacks
- Secure token refresh
- Rate limiting protection

---

## 🤝 Contributing

Contributions are welcome! Please feel free to submit a Pull Request.

1. Fork the project
2. Create your feature branch (`git checkout -b feature/AmazingFeature`)
3. Commit your changes (`git commit -m 'Add some AmazingFeature'`)
4. Push to the branch (`git push origin feature/AmazingFeature`)
5. Open a Pull Request

---

## 📝 Changelog

### Version 1.0.0 (2024-12-04)

- ✨ Initial release
- 🎯 Support for Facebook, Instagram, Twitter, LinkedIn
- 📅 Scheduled posting
- 🔄 Queue support
- 🔐 Encrypted credentials
- 📊 Multi-account management

---

## 📄 License

This package is open-sourced software licensed under the [MIT license](LICENSE).

---

## 💖 Credits

Created with ❤️ for the Laravel community.

---

## 🆘 Support

- **Issues**: [GitHub Issues](https://github.com/socialsync/laravel-social-sync/issues)
- **Documentation**: [Full Docs](docs/)
- **Discussions**: [GitHub Discussions](https://github.com/socialsync/laravel-social-sync/discussions)

---

## ⭐ Show Your Support

Give a ⭐️ if this project helped you!

---

**Made with Laravel • Built for Developers • Perfect for CRMs**
