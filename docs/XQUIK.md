# Xquik Backend

LaraPost keeps the default Twitter / X OAuth driver unchanged. Set
`TWITTER_BACKEND=xquik` when you want Twitter text posts to use Xquik instead.

```env
TWITTER_BACKEND=xquik
XQUIK_API_KEY=
XQUIK_ACCOUNT=@your_handle
XQUIK_API_BASE_URL=https://xquik.com/api/v1
```

The backend sends text posts to `POST /x/tweets`. Media is left on the default
Twitter driver because this package does not yet normalize Xquik media payloads.

If Xquik accepts the write for later confirmation, LaraPost stores the returned
write action as `xquik-write-action:{id}` in the publish response.
