<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>LaraPost OAuth Success</title>
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
            border: 1px solid #e5eaf2;
            border-radius: 14px;
            padding: 1.2rem;
            box-shadow: 0 8px 22px rgba(16, 24, 40, 0.08);
        }

        h1 {
            margin: 0 0 0.4rem;
            color: #0e9f6e;
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
    <h1>Account Connected</h1>
    <p>{{ $message ?? 'Account connected successfully.' }}</p>
    <p>You can close this window if it does not close automatically.</p>
    <p><a href="{{ $dashboardUrl ?? route('larapost.dashboard') }}">Return to dashboard</a></p>
</div>

<script>
(function () {
    var payload = {
        source: 'larapost-oauth',
        status: 'success',
        platform: @json($platform ?? null),
        message: @json($message ?? 'Account connected successfully.')
    };

    if (window.opener && !window.opener.closed) {
        window.opener.postMessage(payload, window.location.origin);
        window.close();
    }
})();
</script>
</body>
</html>
