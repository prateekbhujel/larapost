<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>LaraPost OAuth Error</title>
    <style>
        body {
            font-family: "Inter", "Segoe UI", sans-serif;
            background: #f6f8fb;
            color: #162132;
            padding: 1.25rem;
        }

        .card {
            max-width: 560px;
            margin: 2rem auto;
            background: #fff;
            border: 1px solid #fbcaca;
            border-radius: 14px;
            padding: 1.2rem;
            box-shadow: 0 8px 22px rgba(16, 24, 40, 0.08);
        }

        h1 {
            margin: 0 0 0.4rem;
            color: #c53030;
        }

        p { margin: 0.4rem 0; }

        a {
            color: #ea4b2b;
            text-decoration: none;
            font-weight: 600;
        }
    </style>
</head>
<body>
<div class="card">
    <h1>Connection Failed</h1>
    <p>Error: {{ $error ?? 'Unknown error' }}</p>
    <p>You can close this window and try again.</p>
    <p><a href="{{ $dashboardUrl ?? route('larapost.dashboard') }}">Return to dashboard</a></p>
</div>

<script>
(function () {
    var payload = {
        source: 'larapost-oauth',
        status: 'error',
        platform: @json($platform ?? null),
        error: @json($error ?? 'Unknown error')
    };

    if (window.opener && !window.opener.closed) {
        window.opener.postMessage(payload, window.location.origin);
    }
})();
</script>
</body>
</html>
