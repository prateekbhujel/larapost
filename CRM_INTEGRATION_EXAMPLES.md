# Social Sync - CRM Integration Examples

## Complete Code Examples for Your CRM Application

---

## 1. Lead Management Integration

### Lead Model Setup

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Lead extends Model
{
    protected $fillable = [
        'name',
        'email',
        'phone',
        'company',
        'status',
        'source',
        'notes',
    ];

    protected $casts = [
        'metadata' => 'array',
    ];

    /**
     * Get social posts for this lead
     */
    public function socialPosts()
    {
        return $this->morphMany(\SocialSync\Models\ScheduledPost::class, 'postable');
    }
}
```

### Lead Controller with Auto-Posting

```php
<?php

namespace App\Http\Controllers;

use App\Models\Lead;
use Illuminate\Http\Request;
use SocialSync\Facades\SocialMedia;

class LeadController extends Controller
{
    /**
     * Store a new lead and announce on social media
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string',
            'email' => 'required|email',
            'company' => 'required|string',
            'post_to_social' => 'boolean',
            'social_platforms' => 'array',
        ]);

        // Create the lead
        $lead = Lead::create($validated);

        // Post to social media if requested
        if ($validated['post_to_social'] ?? false) {
            $this->announceNewLead($lead, $validated['social_platforms'] ?? ['linkedin']);
        }

        return redirect()->route('leads.index')
            ->with('success', 'Lead created successfully!');
    }

    /**
     * Announce new lead on social media
     */
    protected function announceNewLead(Lead $lead, array $platforms)
    {
        $content = "🎉 Exciting news! We're now working with {$lead->company}!\n\n";
        $content .= "Welcome to our growing family of clients. ";
        $content .= "Looking forward to a great partnership!\n\n";
        $content .= "#NewClient #Partnership #Growth";

        SocialMedia::post()
            ->content($content)
            ->platforms($platforms)
            ->metadata([
                'lead_id' => $lead->id,
                'type' => 'new_lead_announcement',
            ])
            ->publish();
    }

    /**
     * Schedule milestone announcements
     */
    public function scheduleMilestone(Lead $lead, Request $request)
    {
        $validated = $request->validate([
            'milestone' => 'required|string',
            'scheduled_date' => 'required|date',
            'content' => 'required|string',
        ]);

        SocialMedia::post()
            ->content($validated['content'])
            ->scheduleFor($validated['scheduled_date'])
            ->platforms(['facebook', 'linkedin'])
            ->metadata([
                'lead_id' => $lead->id,
                'milestone' => $validated['milestone'],
            ])
            ->publish();

        return back()->with('success', 'Milestone announcement scheduled!');
    }
}
```

---

## 2. Campaign Management

### Campaign Model

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Campaign extends Model
{
    protected $fillable = [
        'name',
        'description',
        'start_date',
        'end_date',
        'status',
        'target_platforms',
    ];

    protected $casts = [
        'start_date' => 'datetime',
        'end_date' => 'datetime',
        'target_platforms' => 'array',
    ];

    public function posts()
    {
        return $this->hasMany(CampaignPost::class);
    }
}

class CampaignPost extends Model
{
    protected $fillable = [
        'campaign_id',
        'content',
        'scheduled_for',
        'posted_at',
        'platforms',
    ];

    protected $casts = [
        'scheduled_for' => 'datetime',
        'posted_at' => 'datetime',
        'platforms' => 'array',
    ];

    public function campaign()
    {
        return $this->belongsTo(Campaign::class);
    }
}
```

### Campaign Controller

```php
<?php

namespace App\Http\Controllers;

use App\Models\Campaign;
use App\Models\CampaignPost;
use Illuminate\Http\Request;
use SocialSync\Facades\SocialMedia;

class CampaignController extends Controller
{
    /**
     * Launch a campaign - post all scheduled content
     */
    public function launch(Campaign $campaign)
    {
        if ($campaign->status === 'active') {
            return back()->with('error', 'Campaign already active');
        }

        $posts = $campaign->posts()->whereNull('posted_at')->get();

        foreach ($posts as $campaignPost) {
            SocialMedia::post()
                ->content($campaignPost->content)
                ->scheduleFor($campaignPost->scheduled_for)
                ->platforms($campaignPost->platforms)
                ->metadata([
                    'campaign_id' => $campaign->id,
                    'post_id' => $campaignPost->id,
                ])
                ->publish();
        }

        $campaign->update(['status' => 'active']);

        return back()->with('success', "Campaign launched! {$posts->count()} posts scheduled.");
    }

    /**
     * Create a recurring campaign post
     */
    public function createRecurring(Request $request, Campaign $campaign)
    {
        $validated = $request->validate([
            'content_template' => 'required|string',
            'frequency' => 'required|in:daily,weekly,monthly',
            'times' => 'required|array',
            'duration_days' => 'required|integer|min:1',
        ]);

        $startDate = now();
        $endDate = $startDate->copy()->addDays($validated['duration_days']);

        $scheduledDates = $this->generateScheduleDates(
            $startDate,
            $endDate,
            $validated['frequency'],
            $validated['times']
        );

        foreach ($scheduledDates as $date) {
            SocialMedia::post()
                ->content($validated['content_template'])
                ->scheduleFor($date)
                ->platforms($campaign->target_platforms)
                ->metadata([
                    'campaign_id' => $campaign->id,
                    'recurring' => true,
                ])
                ->publish();
        }

        return back()->with('success', count($scheduledDates) . ' posts scheduled!');
    }

    /**
     * Generate schedule dates based on frequency
     */
    protected function generateScheduleDates($start, $end, $frequency, $times)
    {
        $dates = [];
        $current = $start->copy();

        while ($current->lte($end)) {
            foreach ($times as $time) {
                $dateTime = $current->copy()->setTimeFromTimeString($time);
                if ($dateTime->gt(now())) {
                    $dates[] = $dateTime;
                }
            }

            switch ($frequency) {
                case 'daily':
                    $current->addDay();
                    break;
                case 'weekly':
                    $current->addWeek();
                    break;
                case 'monthly':
                    $current->addMonth();
                    break;
            }
        }

        return $dates;
    }
}
```

---

## 3. Product Launch Integration

### Product Model

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    protected $fillable = [
        'name',
        'description',
        'price',
        'launch_date',
        'image_url',
        'is_published',
    ];

    protected $casts = [
        'launch_date' => 'datetime',
        'is_published' => 'boolean',
    ];
}
```

### Product Controller with Social Integration

```php
<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;
use SocialSync\Facades\SocialMedia;

class ProductController extends Controller
{
    /**
     * Publish product and announce on social media
     */
    public function publish(Product $product)
    {
        $product->update(['is_published' => true]);

        // Create announcement content
        $content = "🚀 New Product Launch!\n\n";
        $content .= "{$product->name}\n\n";
        $content .= "{$product->description}\n\n";
        $content .= "Learn more: " . route('products.show', $product) . "\n\n";
        $content .= "#NewProduct #Launch #Innovation";

        // Post immediately
        $results = SocialMedia::post()
            ->content($content)
            ->image($product->image_url)
            ->platforms(['facebook', 'instagram', 'twitter', 'linkedin'])
            ->metadata([
                'product_id' => $product->id,
                'type' => 'product_launch',
            ])
            ->publish();

        return back()->with('success', 'Product published and announced on social media!');
    }

    /**
     * Schedule product teaser campaign
     */
    public function scheduleTeaser(Product $product, Request $request)
    {
        $launchDate = $product->launch_date;

        // Schedule teasers
        $teasers = [
            [
                'days_before' => 7,
                'content' => "Something amazing is coming... 🤫\n\n#StayTuned",
            ],
            [
                'days_before' => 3,
                'content' => "3 days until the big reveal! 🎉\n\nGet ready for {$product->name}",
            ],
            [
                'days_before' => 1,
                'content' => "Tomorrow is the day! 🚀\n\nDon't miss our new product launch!",
            ],
        ];

        foreach ($teasers as $teaser) {
            $scheduledDate = $launchDate->copy()->subDays($teaser['days_before'])->setTime(10, 0);

            SocialMedia::post()
                ->content($teaser['content'])
                ->scheduleFor($scheduledDate)
                ->platforms(['facebook', 'instagram', 'twitter'])
                ->metadata([
                    'product_id' => $product->id,
                    'type' => 'product_teaser',
                ])
                ->publish();
        }

        return back()->with('success', 'Teaser campaign scheduled!');
    }
}
```

---

## 4. Event Management

### Event Announcements

```php
<?php

namespace App\Http\Controllers;

use App\Models\Event;
use Illuminate\Http\Request;
use SocialSync\Facades\SocialMedia;
use Carbon\Carbon;

class EventController extends Controller
{
    /**
     * Create event and schedule social posts
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string',
            'description' => 'required|string',
            'event_date' => 'required|date',
            'location' => 'required|string',
            'auto_post' => 'boolean',
        ]);

        $event = Event::create($validated);

        if ($validated['auto_post'] ?? false) {
            $this->scheduleEventAnnouncements($event);
        }

        return redirect()->route('events.index');
    }

    /**
     * Schedule event announcements
     */
    protected function scheduleEventAnnouncements(Event $event)
    {
        $eventDate = Carbon::parse($event->event_date);

        $announcements = [
            // 2 weeks before
            [
                'date' => $eventDate->copy()->subWeeks(2),
                'content' => "📅 Save the Date!\n\n{$event->title}\n{$eventDate->format('F j, Y')}\n{$event->location}\n\n#Event #SaveTheDate",
            ],
            // 1 week before
            [
                'date' => $eventDate->copy()->subWeek(),
                'content' => "⏰ One week to go!\n\n{$event->title} is happening soon!\n\nRegister now: " . route('events.show', $event),
            ],
            // 1 day before
            [
                'date' => $eventDate->copy()->subDay(),
                'content' => "🎉 Tomorrow!\n\n{$event->title}\n\nSee you there! Last chance to register.",
            ],
            // Day of event
            [
                'date' => $eventDate->copy()->setTime(8, 0),
                'content' => "🚀 Today's the day!\n\n{$event->title} starts soon!\n\nLocation: {$event->location}",
            ],
        ];

        foreach ($announcements as $announcement) {
            if ($announcement['date']->gt(now())) {
                SocialMedia::post()
                    ->content($announcement['content'])
                    ->scheduleFor($announcement['date'])
                    ->platforms(['facebook', 'linkedin', 'twitter'])
                    ->metadata(['event_id' => $event->id])
                    ->publish();
            }
        }
    }
}
```

---

## 5. Content Calendar Integration

### Content Calendar Service

```php
<?php

namespace App\Services;

use SocialSync\Facades\SocialMedia;
use Carbon\Carbon;

class ContentCalendarService
{
    /**
     * Schedule a full month of content
     */
    public function scheduleMonth(array $contentPlan)
    {
        $scheduled = [];

        foreach ($contentPlan as $item) {
            $post = SocialMedia::post()
                ->content($item['content'])
                ->scheduleFor($item['date'])
                ->platforms($item['platforms']);

            if (isset($item['image'])) {
                $post->image($item['image']);
            }

            $result = $post->metadata([
                'content_calendar_id' => $item['id'] ?? null,
                'category' => $item['category'] ?? 'general',
            ])->publish();

            $scheduled[] = $result;
        }

        return $scheduled;
    }

    /**
     * Generate optimal posting schedule
     */
    public function generateOptimalSchedule($startDate, $endDate, $postsPerWeek = 5)
    {
        $optimalTimes = [
            'facebook' => ['10:00', '14:00', '19:00'],
            'instagram' => ['11:00', '15:00', '20:00'],
            'twitter' => ['09:00', '12:00', '17:00'],
            'linkedin' => ['08:00', '12:00', '17:00'],
        ];

        $schedule = [];
        $current = Carbon::parse($startDate);
        $end = Carbon::parse($endDate);

        while ($current->lte($end)) {
            if ($current->isWeekday()) {
                foreach ($optimalTimes as $platform => $times) {
                    $time = $times[array_rand($times)];
                    $schedule[] = [
                        'date' => $current->copy()->setTimeFromTimeString($time),
                        'platform' => $platform,
                    ];
                }
            }
            $current->addDay();
        }

        return $schedule;
    }
}
```

---

## 6. Listening to Events

### Custom Event Listeners

```php
<?php

namespace App\Listeners;

use SocialSync\Events\PostPublished;
use App\Models\Lead;
use Illuminate\Support\Facades\Log;

class LogSuccessfulPost
{
    public function handle(PostPublished $event)
    {
        $post = $event->scheduledPost;
        $result = $event->result;

        // Log success
        Log::info('Social post published successfully', [
            'post_id' => $post->id,
            'platform' => $post->platform,
            'platform_post_id' => $result['id'] ?? null,
        ]);

        // Update related model if exists
        if ($leadId = $post->metadata['lead_id'] ?? null) {
            $lead = Lead::find($leadId);
            $lead?->update(['last_social_post_at' => now()]);
        }
    }
}
```

```php
<?php

namespace App\Listeners;

use SocialSync\Events\PostFailed;
use App\Notifications\SocialPostFailedNotification;
use Illuminate\Support\Facades\Notification;

class AlertOnPostFailure
{
    public function handle(PostFailed $event)
    {
        $post = $event->scheduledPost;
        $error = $event->errorMessage;

        // Notify administrators
        $admins = User::where('role', 'admin')->get();

        Notification::send($admins, new SocialPostFailedNotification($post, $error));

        // Log for debugging
        Log::error('Social post failed', [
            'post_id' => $post->id,
            'platform' => $post->platform,
            'error' => $error,
            'retry_count' => $post->retry_count,
        ]);
    }
}
```

### Register Listeners in EventServiceProvider

```php
<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    protected $listen = [
        \SocialSync\Events\PostPublished::class => [
            \App\Listeners\LogSuccessfulPost::class,
        ],
        \SocialSync\Events\PostFailed::class => [
            \App\Listeners\AlertOnPostFailure::class,
        ],
    ];
}
```

---

## 7. Custom Commands

### Bulk Import Social Content

```php
<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use SocialSync\Facades\SocialMedia;
use Carbon\Carbon;

class ImportSocialContent extends Command
{
    protected $signature = 'social:import {file}';
    protected $description = 'Import and schedule social content from CSV';

    public function handle()
    {
        $file = $this->argument('file');

        if (!file_exists($file)) {
            $this->error('File not found');
            return 1;
        }

        $csv = array_map('str_getcsv', file($file));
        $headers = array_shift($csv);

        $scheduled = 0;

        foreach ($csv as $row) {
            $data = array_combine($headers, $row);

            SocialMedia::post()
                ->content($data['content'])
                ->scheduleFor(Carbon::parse($data['scheduled_for']))
                ->platforms(explode(',', $data['platforms']))
                ->publish();

            $scheduled++;
        }

        $this->info("Scheduled {$scheduled} posts successfully!");

        return 0;
    }
}
```

---

## Usage in Routes

```php
// routes/web.php

Route::prefix('crm')->middleware('auth')->group(function () {

    // Lead management with social integration
    Route::resource('leads', LeadController::class);
    Route::post('leads/{lead}/announce', [LeadController::class, 'announceNewLead']);

    // Campaign management
    Route::resource('campaigns', CampaignController::class);
    Route::post('campaigns/{campaign}/launch', [CampaignController::class, 'launch']);

    // Product management
    Route::resource('products', ProductController::class);
    Route::post('products/{product}/publish', [ProductController::class, 'publish']);

    // Events
    Route::resource('events', EventController::class);

});
```

These examples show how to fully integrate Social Sync into your CRM application!
