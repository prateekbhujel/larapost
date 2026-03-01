# CRM Integration Examples

## Lead Created Event

```php
use SocialSync\Facades\SocialMedia;

SocialMedia::post()
    ->content("New lead captured: {$lead->name}")
    ->platforms(['linkedin'])
    ->publish();
```

## Scheduled Campaign

```php
SocialMedia::post()
    ->content('Weekly campaign update')
    ->platforms(['facebook', 'twitter'])
    ->scheduleFor(now()->addDay())
    ->publish();
```

## Handle Result Payloads

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
