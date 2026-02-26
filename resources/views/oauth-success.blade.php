<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Social Sync Connected</title>
</head>
<body>
    <h1>Account Connected</h1>
    <p>{{ $message ?? 'Account connected successfully.' }}</p>
    <p>Platform: {{ ucfirst($platform ?? 'unknown') }}</p>
    @isset($account)
        <p>Account ID: {{ $account->id }}</p>
        <p>Name: {{ $account->account_name }}</p>
    @endisset
</body>
</html>
