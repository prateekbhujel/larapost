<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Social Sync Test Dashboard</title>
</head>
<body>
    <h1>Social Sync Test Dashboard</h1>

    @if (session('success'))
        <p>{{ session('success') }}</p>
    @endif

    @if (session('error'))
        <p>{{ session('error') }}</p>
    @endif

    <h2>Connected Accounts</h2>
    <ul>
        @forelse ($accounts as $account)
            <li>{{ $account->platform }} - {{ $account->account_name }}</li>
        @empty
            <li>No active accounts connected.</li>
        @endforelse
    </ul>

    <h2>Recent Posts</h2>
    <ul>
        @forelse ($recentPosts as $post)
            <li>#{{ $post->id }} - {{ $post->status }} - {{ $post->content }}</li>
        @empty
            <li>No posts yet.</li>
        @endforelse
    </ul>
</body>
</html>
