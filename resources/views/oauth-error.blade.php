<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>LaraPost OAuth Error</title>
</head>
<body style="font-family: Arial, sans-serif; padding: 24px;">
    <h1>Connection Failed</h1>
    <p>Error: {{ $error ?? 'Unknown error' }}</p>
    <p><a href="{{ route('larapost.dashboard') }}">Return to dashboard</a></p>
</body>
</html>
