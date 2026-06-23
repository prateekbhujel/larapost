# Xquik Backend

LaraPost keeps the default Twitter / X OAuth driver unchanged. Set
`TWITTER_BACKEND=xquik` when you want Twitter text posts to use Xquik instead.

```env
TWITTER_BACKEND=xquik
XQUIK_API_KEY=
XQUIK_ACCOUNT=@your_handle
XQUIK_API_BASE_URL=https://xquik.com/api/v1
```

The backend sends text posts to `POST /api/v1/x/tweets` with the documented
`x-api-key` header. Media, replies, and OAuth account discovery stay on the
default Twitter driver because this package does not yet normalize those Xquik
payloads.

If Xquik accepts the write for later confirmation, LaraPost stores the returned
write action as `xquik-write-action:{id}` in the publish response.

## Dashboard And Accounts

The dashboard can save `backend=xquik`, `xquik_api_key`, `xquik_account`, and
`xquik_api_base_url`. It does not start Twitter OAuth for Xquik. Create the
Twitter account row manually or with a seed:

```php
SocialSync\Models\SocialAccount::query()->updateOrCreate(
    ['platform' => 'twitter', 'account_id_on_platform' => 'xquik-primary'],
    [
        'account_name' => 'Xquik Primary',
        'account_username' => '@your_handle',
        'credentials' => ['account' => '@your_handle'],
        'is_active' => true,
    ],
);
```

`verifyCredentials()` for the Xquik backend is a local configuration check. It
confirms an API key and account are present, but it does not call a remote verify
endpoint.

## Response Shape

The public OpenAPI document at `https://xquik.com/openapi.json` documents
`POST /api/v1/x/tweets` with success statuses `200` and `202`, `tweetId` for a
confirmed tweet, and `writeActionId` for writes that need later confirmation.
LaraPost also accepts `id` and `data.id` defensively for compatible response
wrappers. A pending `writeActionId` means Xquik accepted the write for later
confirmation, so LaraPost returns status `accepted` and stores
`write_action_id`. Xquik exposes `GET /api/v1/x/write-actions/{id}` for polling
that pending write outside this package.
