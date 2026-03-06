<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ config('larapost.ui.title', 'LaraPost Dashboard') }}</title>
    <style>
        :root {
            --bg: #f6f8fb;
            --card: #ffffff;
            --line: #e5eaf2;
            --text: #162132;
            --muted: #5f6f85;
            --brand: #ea4b2b;
            --brand-dark: #c83a1f;
            --ok: #0e9f6e;
            --bad: #dd3b4b;
            --warn: #b7791f;
            --info: #2563eb;
        }

        * { box-sizing: border-box; }
        body {
            margin: 0;
            font-family: "Inter", "Segoe UI", sans-serif;
            background: radial-gradient(circle at top right, #ffe7e1 0%, transparent 36%), var(--bg);
            color: var(--text);
        }

        .shell {
            max-width: 1240px;
            margin: 0 auto;
            padding: 1.25rem;
        }

        .header {
            display: flex;
            flex-wrap: wrap;
            justify-content: space-between;
            gap: 0.75rem;
            margin-bottom: 1rem;
        }

        h1 {
            margin: 0;
            font-size: clamp(1.4rem, 3vw, 2rem);
        }

        .sub {
            margin-top: 0.35rem;
            color: var(--muted);
            font-size: 0.94rem;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border: 1px solid transparent;
            border-radius: 10px;
            padding: 0.55rem 0.9rem;
            font-size: 0.88rem;
            font-weight: 600;
            text-decoration: none;
            cursor: pointer;
            transition: 0.18s ease;
        }

        .btn-primary {
            background: var(--brand);
            color: white;
        }

        .btn-primary:hover { background: var(--brand-dark); }

        .btn-ghost {
            border-color: var(--line);
            background: #fff;
            color: var(--text);
        }

        .btn-ghost:hover { background: #f2f5fa; }

        .alert {
            border-radius: 12px;
            padding: 0.75rem 0.95rem;
            margin-bottom: 0.85rem;
            border: 1px solid;
            font-size: 0.92rem;
        }
        .alert-success { color: #0b5c40; background: #def8ee; border-color: #b7f0d8; }
        .alert-error { color: #7f1d1d; background: #fee8e8; border-color: #fecaca; }

        .grid {
            display: grid;
            gap: 1rem;
        }

        .cols-main {
            grid-template-columns: 1fr;
        }

        @media (min-width: 1024px) {
            .cols-main {
                grid-template-columns: 1.15fr 0.85fr;
            }
        }

        .card {
            background: var(--card);
            border: 1px solid var(--line);
            border-radius: 16px;
            padding: 1rem;
            box-shadow: 0 8px 24px rgba(15, 25, 40, 0.05);
        }

        .card h2 {
            margin: 0;
            font-size: 1.12rem;
        }

        .muted { color: var(--muted); }

        .platform-grid {
            display: grid;
            gap: 0.75rem;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        }

        .platform {
            border: 1px solid var(--line);
            border-radius: 12px;
            padding: 0.85rem;
            background: #fcfdff;
        }

        .row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 0.75rem;
            flex-wrap: wrap;
        }

        .badge {
            font-size: 0.73rem;
            font-weight: 700;
            border-radius: 999px;
            padding: 0.2rem 0.55rem;
            border: 1px solid;
        }

        .badge-ok { color: #0b5c40; border-color: #86efac; background: #def7ec; }
        .badge-off { color: #7c4a03; border-color: #fcd34d; background: #fff7db; }

        .field-grid {
            margin-top: 0.7rem;
            display: grid;
            gap: 0.55rem;
        }

        label {
            display: block;
            font-size: 0.82rem;
            color: var(--muted);
            margin-bottom: 0.2rem;
        }

        input, textarea, select {
            width: 100%;
            border: 1px solid #d3dbe8;
            border-radius: 10px;
            padding: 0.55rem 0.7rem;
            font: inherit;
            color: var(--text);
            background: white;
        }

        textarea { min-height: 120px; resize: vertical; }

        .stack { display: grid; gap: 0.75rem; }

        .checks {
            display: flex;
            flex-wrap: wrap;
            gap: 0.55rem 0.85rem;
        }

        .checks label {
            margin: 0;
            font-size: 0.88rem;
            color: var(--text);
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.88rem;
        }

        th, td {
            text-align: left;
            padding: 0.55rem;
            border-bottom: 1px solid var(--line);
            vertical-align: top;
        }

        th { color: var(--muted); font-weight: 600; }

        .status {
            font-size: 0.73rem;
            font-weight: 700;
            border-radius: 999px;
            padding: 0.2rem 0.5rem;
            display: inline-block;
        }

        .st-published { color: #0b5c40; background: #def7ec; }
        .st-failed { color: #991b1b; background: #fee2e2; }
        .st-pending, .st-processing { color: #92400e; background: #fef3c7; }

        .small { font-size: 0.8rem; }
        .result-list { margin: 0.4rem 0 0; padding-left: 1rem; }
    </style>
</head>
<body>
<div class="shell">
    <header class="header">
        <div>
            <h1>{{ config('larapost.ui.title', 'LaraPost Dashboard') }}</h1>
            <p class="sub">Connect accounts, configure app credentials, and publish/schedule content from one place.</p>
        </div>
        <div>
            <a class="btn btn-ghost" href="{{ route('larapost.dashboard') }}">Refresh</a>
        </div>
    </header>

    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    @if (session('error'))
        <div class="alert alert-error">{{ session('error') }}</div>
    @endif

    @if ($errors->any())
        <div class="alert alert-error">
            <strong>Validation failed:</strong>
            <ul class="result-list">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    @if (!empty($publishResults))
        <div class="alert alert-success">
            <strong>Last publish run results:</strong>
            <ul class="result-list">
                @foreach ($publishResults as $result)
                    <li>
                        {{ strtoupper($result['platform'] ?? 'unknown') }}
                        · account #{{ $result['account_id'] ?? 'n/a' }}
                        · {{ ($result['success'] ?? false) ? 'OK' : ('FAILED: ' . ($result['error'] ?? 'Unknown error')) }}
                    </li>
                @endforeach
            </ul>
        </div>
    @endif

    <section class="card" style="margin-bottom: 1rem;">
        <div class="row">
            <div>
                <h2>Provider Connection & App Credentials</h2>
                <p class="muted small">Use either environment variables or save credentials below. Saved credentials override env values.</p>
            </div>
        </div>

        <div class="platform-grid" style="margin-top: 0.8rem;">
            @foreach ($platforms as $platform)
                <article class="platform">
                    <div class="row">
                        <strong>{{ ucfirst($platform['key']) }}</strong>
                        @if ($platform['configured'])
                            <span class="badge badge-ok">Configured ({{ $platform['source'] }})</span>
                        @else
                            <span class="badge badge-off">Missing credentials</span>
                        @endif
                    </div>

                    <p class="muted small" style="margin: 0.45rem 0 0.7rem;">
                        Accounts: {{ $platform['active_accounts'] }}/{{ $platform['total_accounts'] }} active
                    </p>

                    <div class="row" style="margin-bottom: 0.55rem;">
                        <a class="btn btn-primary" data-larapost-oauth-popup="1" href="{{ route('larapost.connect', ['platform' => $platform['key'], 'mode' => 'popup']) }}">Login with {{ ucfirst($platform['key']) }}</a>
                    </div>

                    <form method="POST" action="{{ route('larapost.settings.store', ['platform' => $platform['key']]) }}">
                        @csrf
                        <div class="field-grid">
                            @foreach ($platform['fields'] as $field => $meta)
                                @php
                                    $isSecret = str_contains($field, 'secret');
                                    $value = old($field, $isSecret ? '' : ($platform['saved_credentials'][$field] ?? ''));
                                @endphp
                                <div>
                                    <label for="{{ $platform['key'] }}_{{ $field }}">{{ $meta['label'] }}</label>
                                    <input
                                        id="{{ $platform['key'] }}_{{ $field }}"
                                        name="{{ $field }}"
                                        type="{{ $isSecret ? 'password' : 'text' }}"
                                        value="{{ $value }}"
                                        placeholder="{{ $isSecret && isset($platform['saved_credentials'][$field]) ? 'Saved (enter to replace)' : '' }}"
                                    />
                                </div>
                            @endforeach
                        </div>

                        <div class="row" style="margin-top: 0.65rem;">
                            <button class="btn btn-primary" type="submit">Save</button>
                        </div>
                    </form>

                    <form method="POST" action="{{ route('larapost.settings.destroy', ['platform' => $platform['key']]) }}" style="margin-top: 0.45rem;">
                        @csrf
                        @method('DELETE')
                        <button class="btn btn-ghost" type="submit">Clear Saved Credentials</button>
                    </form>
                </article>
            @endforeach
        </div>
    </section>

    <div class="grid cols-main">
        <section class="card stack">
            <div>
                <h2>Publish / Schedule</h2>
                <p class="muted small">Create a post now or schedule it for later.</p>
            </div>

            <form method="POST" action="{{ route('larapost.publish') }}" class="stack">
                @csrf
                <div>
                    <label for="content">Content</label>
                    <textarea id="content" name="content" required>{{ old('content') }}</textarea>
                </div>

                <div>
                    <label>Platforms</label>
                    <div class="checks">
                        @foreach ($platforms as $platform)
                            <label>
                                <input type="checkbox" name="platforms[]" value="{{ $platform['key'] }}" {{ in_array($platform['key'], old('platforms', []), true) ? 'checked' : '' }}>
                                {{ ucfirst($platform['key']) }}
                            </label>
                        @endforeach
                    </div>
                </div>

                <div class="grid" style="grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 0.75rem;">
                    <div>
                        <label for="media_url">Media URL (optional)</label>
                        <input id="media_url" name="media_url" type="url" value="{{ old('media_url') }}" placeholder="https://cdn.example.com/image.jpg">
                    </div>
                    <div>
                        <label for="media_type">Media Type</label>
                        <select id="media_type" name="media_type">
                            <option value="image" {{ old('media_type', 'image') === 'image' ? 'selected' : '' }}>Image</option>
                            <option value="video" {{ old('media_type') === 'video' ? 'selected' : '' }}>Video</option>
                        </select>
                    </div>
                </div>

                <div>
                    <label for="schedule_for">Schedule For (optional)</label>
                    <input id="schedule_for" name="schedule_for" type="datetime-local" value="{{ old('schedule_for') }}">
                </div>

                <div>
                    <button class="btn btn-primary" type="submit">Publish / Schedule</button>
                </div>
            </form>
        </section>

        <section class="card stack">
            <div>
                <h2>Connected Accounts</h2>
                <p class="muted small">Enable/disable accounts used for publish workflows.</p>
            </div>

            <table>
                <thead>
                <tr>
                    <th>Platform</th>
                    <th>Account</th>
                    <th>Status</th>
                    <th>Action</th>
                </tr>
                </thead>
                <tbody>
                @forelse ($accounts as $account)
                    <tr>
                        <td>{{ ucfirst($account->platform) }}</td>
                        <td>
                            <div>{{ $account->account_name }}</div>
                            <div class="muted small">{{ $account->account_username ?: $account->account_id_on_platform }}</div>
                        </td>
                        <td>{{ $account->is_active ? 'Active' : 'Inactive' }}</td>
                        <td>
                            <form method="POST" action="{{ route('larapost.accounts.toggle', ['account' => $account->id]) }}">
                                @csrf
                                <button class="btn btn-ghost" type="submit">{{ $account->is_active ? 'Disable' : 'Enable' }}</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="muted">No connected accounts yet.</td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </section>
    </div>

    <section class="card" style="margin-top: 1rem;">
        <h2>Recent Posts</h2>
        <table style="margin-top: 0.55rem;">
            <thead>
            <tr>
                <th>ID</th>
                <th>Platform</th>
                <th>Status</th>
                <th>When</th>
                <th>Content</th>
            </tr>
            </thead>
            <tbody>
            @forelse ($recentPosts as $post)
                @php
                    $statusClass = match ($post->status) {
                        'published' => 'st-published',
                        'failed' => 'st-failed',
                        'pending', 'processing' => 'st-pending',
                        default => 'st-processing',
                    };
                @endphp
                <tr>
                    <td>#{{ $post->id }}</td>
                    <td>{{ ucfirst($post->account->platform ?? 'n/a') }}</td>
                    <td><span class="status {{ $statusClass }}">{{ strtoupper($post->status) }}</span></td>
                    <td class="small">
                        @if ($post->scheduled_for)
                            Scheduled: {{ $post->scheduled_for->format('Y-m-d H:i') }}<br>
                        @endif
                        Created: {{ $post->created_at?->format('Y-m-d H:i') }}
                    </td>
                    <td>{{ \Illuminate\Support\Str::limit($post->content, 120) }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="5" class="muted">No posts created yet.</td>
                </tr>
            @endforelse
            </tbody>
        </table>
    </section>
</div>
<script>
(function () {
    var links = document.querySelectorAll('[data-larapost-oauth-popup="1"]');

    if (!links.length) {
        return;
    }

    function popupFeatures(width, height) {
        var left = Math.max(0, Math.round((window.screen.width - width) / 2));
        var top = Math.max(0, Math.round((window.screen.height - height) / 2));
        return 'popup=yes,width=' + width + ',height=' + height + ',left=' + left + ',top=' + top + ',resizable=yes,scrollbars=yes';
    }

    links.forEach(function (link) {
        link.addEventListener('click', function (event) {
            event.preventDefault();

            var url = link.getAttribute('href');
            var popup = window.open(url, 'larapost-oauth', popupFeatures(620, 760));

            if (!popup) {
                window.location.href = url;
                return;
            }

            popup.focus();
        });
    });

    window.addEventListener('message', function (event) {
        if (event.origin !== window.location.origin) {
            return;
        }

        if (!event.data || event.data.source !== 'larapost-oauth') {
            return;
        }

        window.location.href = '{{ route('larapost.dashboard') }}';
    });
})();
</script>
</body>
</html>
