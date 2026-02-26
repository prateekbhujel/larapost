<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Social Sync Error</title>
</head>
<body>
    <h1>Connection Failed</h1>
    <p>Platform: {{ ucfirst($platform ?? 'unknown') }}</p>
    <p>Error: {{ $error ?? 'Unknown error' }}</p>
</body>
</html>
