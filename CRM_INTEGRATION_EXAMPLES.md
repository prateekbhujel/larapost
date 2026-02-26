# CRM Integration Examples

## Lead created event

```php
use SocialSync\Facades\SocialMedia;

SocialMedia::post()
    ->content("New lead captured: {$lead->name}")
    ->platforms(['linkedin'])
    ->publish();
```

## Scheduled campaign

```php
SocialMedia::post()
    ->content('Weekly campaign update')
    ->platforms(['facebook', 'twitter'])
    ->scheduleFor(now()->addDay())
    ->publish();
```

## Handling results

```php
$results = SocialMedia::post()
    ->content('Product launch update')
    ->platforms(['facebook'])
    ->publish();

foreach ($results as $result) {
    if (! $result['success']) {
        logger()->error('Social publish failed', $result);
    }
}
```
