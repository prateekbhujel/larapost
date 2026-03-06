<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>LaraPost OAuth Success</title>
</head>
<body style="font-family: Arial, sans-serif; padding: 24px;">
    <h1>Account Connected</h1>
    <p>{{ $message ?? 'Account connected successfully.' }}</p>
    <p><a href="{{ route('larapost.dashboard') }}">Go to dashboard</a></p>
</body>
</html>
