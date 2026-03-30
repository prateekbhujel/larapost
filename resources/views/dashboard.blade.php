<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ config('larapost.ui.title', 'LaraPost Dashboard') }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        display: ['Space Grotesk', 'sans-serif'],
                        body: ['IBM Plex Sans', 'sans-serif'],
                    },
                    colors: {
                        brand: {
                            50: '#fff6ed',
                            100: '#ffead5',
                            500: '#ef6b2e',
                            600: '#dc5a1f',
                            700: '#b64413',
                        }
                    }
                }
            }
        };
    </script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Sans:wght@400;500;600;700&family=Space+Grotesk:wght@500;600;700&display=swap" rel="stylesheet">
</head>
<body class="min-h-screen bg-slate-100 text-slate-900 font-body">
    <div class="pointer-events-none fixed inset-0 overflow-hidden">
        <div class="absolute -top-40 -left-20 h-96 w-96 rounded-full bg-brand-100 blur-3xl opacity-70"></div>
        <div class="absolute top-28 -right-24 h-[26rem] w-[26rem] rounded-full bg-cyan-100 blur-3xl opacity-70"></div>
    </div>

    @php
        $dashboardTimezone = config('app.timezone', 'UTC');
    @endphp

    <main class="relative mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
        <header class="mb-6 rounded-3xl border border-slate-200 bg-white/90 p-6 shadow-sm backdrop-blur">
            <div class="flex flex-wrap items-center justify-between gap-4">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-[0.22em] text-brand-600">LaraPost</p>
                    <h1 class="mt-1 font-display text-3xl font-semibold text-slate-900">{{ config('larapost.ui.title', 'Social Publishing Control Panel') }}</h1>
                    <p class="mt-2 max-w-3xl text-sm text-slate-600">Connect Facebook Pages, Twitter / X accounts, and LinkedIn profiles, then publish or schedule content across the exact destinations you want.</p>
                </div>
                <div class="flex flex-wrap items-center gap-3">
                    <span class="inline-flex items-center rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 text-xs font-semibold uppercase tracking-[0.18em] text-slate-600">Timezone · {{ $dashboardTimezone }}</span>
                    <a href="{{ route('larapost.dashboard') }}" class="inline-flex items-center rounded-xl border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 transition hover:border-slate-400 hover:bg-slate-50">Refresh</a>
                </div>
            </div>
        </header>

        <section class="mb-6 rounded-3xl border border-slate-200 bg-white/95 p-5 shadow-sm backdrop-blur sm:p-6">
            <div class="flex flex-wrap items-start justify-between gap-4">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-[0.22em] text-brand-600">Support Scope</p>
                    <h2 class="mt-1 font-display text-2xl font-semibold text-slate-900">What this dashboard supports in v1.0.0</h2>
                </div>
                <span class="inline-flex items-center rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 text-xs font-semibold uppercase tracking-[0.18em] text-slate-600">Honest shipping surface</span>
            </div>
            <div class="mt-4 grid gap-3 md:grid-cols-3">
                <article class="rounded-2xl border border-slate-200 bg-slate-50/70 p-4">
                    <h3 class="font-display text-lg font-semibold text-slate-900">Facebook Pages</h3>
                    <p class="mt-2 text-sm text-slate-600">Connect one Facebook login, sync multiple Pages, and publish or schedule Page posts. Personal profile posting is not part of this release.</p>
                </article>
                <article class="rounded-2xl border border-slate-200 bg-slate-50/70 p-4">
                    <h3 class="font-display text-lg font-semibold text-slate-900">Twitter / X</h3>
                    <p class="mt-2 text-sm text-slate-600">OAuth and text publishing are supported. Posting still depends on your X developer app having the required write access and billing or credits.</p>
                </article>
                <article class="rounded-2xl border border-slate-200 bg-slate-50/70 p-4">
                    <h3 class="font-display text-lg font-semibold text-slate-900">LinkedIn Profiles</h3>
                    <p class="mt-2 text-sm text-slate-600">OAuth and personal profile posting are supported. Organization pages and unsupported provider flows are intentionally not exposed here.</p>
                </article>
            </div>
        </section>

        @if (session('success'))
            <div class="mb-4 rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-emerald-700">
                {{ session('success') }}
            </div>
        @endif

        @if (session('error'))
            <div class="mb-4 rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-rose-700">
                {{ session('error') }}
            </div>
        @endif

        @if ($errors->any())
            <div class="mb-4 rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-rose-700">
                <p class="font-semibold">Validation failed:</p>
                <ul class="mt-2 list-disc pl-5 text-sm">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        @if (!empty($publishResults))
            <div class="mb-4 rounded-2xl border border-sky-200 bg-sky-50 px-4 py-3 text-sky-900">
                <p class="font-semibold">Last publish run results:</p>
                <ul class="mt-2 list-disc pl-5 text-sm">
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

        <section class="mb-6 rounded-3xl border border-slate-200 bg-white/95 p-5 shadow-sm backdrop-blur sm:p-6">
            <div class="mb-4 flex flex-wrap items-start justify-between gap-3">
                <div>
                    <h2 class="font-display text-2xl font-semibold text-slate-900">Provider Connection & App Credentials</h2>
                    <p class="mt-1 text-sm text-slate-600">Save credentials once, then use the login popup to connect Facebook Pages, Twitter / X accounts, or LinkedIn profiles.</p>
                </div>
            </div>

            <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                @foreach ($platforms as $platform)
                    @php
                        $platformLabel = match ($platform['key']) {
                            'facebook' => 'Facebook Pages',
                            'twitter' => 'Twitter / X',
                            'linkedin' => 'LinkedIn Profiles',
                            default => ucfirst($platform['key']),
                        };

                        $consoleUrl = match ($platform['key']) {
                            'facebook' => 'https://developers.facebook.com/apps/',
                            'twitter' => 'https://developer.x.com/en/portal/dashboard',
                            'linkedin' => 'https://www.linkedin.com/developers/apps',
                            default => '#',
                        };
                    @endphp

                    <article class="rounded-2xl border border-slate-200 bg-slate-50/60 p-4">
                        <div class="flex items-center justify-between gap-2">
                            <h3 class="font-display text-xl font-semibold text-slate-900">{{ $platformLabel }}</h3>
                            @if ($platform['configured'])
                                <span class="rounded-full border border-emerald-300 bg-emerald-100 px-2.5 py-1 text-xs font-semibold uppercase tracking-wide text-emerald-700">Configured</span>
                            @else
                                <span class="rounded-full border border-amber-300 bg-amber-100 px-2.5 py-1 text-xs font-semibold uppercase tracking-wide text-amber-700">Missing credentials</span>
                            @endif
                        </div>

                        <p class="mt-2 text-sm text-slate-600">Accounts: {{ $platform['active_accounts'] }}/{{ $platform['total_accounts'] }} active</p>

                        <div class="mt-3">
                            @if ($platform['configured'])
                                <a class="inline-flex w-full items-center justify-center rounded-xl bg-brand-500 px-3 py-2 text-sm font-semibold text-white transition hover:bg-brand-600" data-larapost-oauth-popup="1" href="{{ route('larapost.connect', ['platform' => $platform['key'], 'mode' => 'popup']) }}">Login with {{ $platformLabel }}</a>
                            @else
                                <button type="button" class="inline-flex w-full cursor-not-allowed items-center justify-center rounded-xl border border-slate-300 bg-slate-100 px-3 py-2 text-sm font-semibold text-slate-500" disabled>Save credentials first</button>
                            @endif
                        </div>

                        @if (! $platform['configured'])
                            <p class="mt-2 text-xs text-slate-500">Save credentials below, then click Login with {{ $platformLabel }}.</p>
                        @else
                            <p class="mt-2 text-xs text-slate-500">Connect again anytime to sync newly granted Pages or accounts.</p>
                        @endif

                        <div class="mt-3 rounded-xl border border-slate-200 bg-white p-3">
                            <label for="{{ $platform['key'] }}_callback_url" class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500">OAuth Callback URL</label>
                            <div class="flex items-center gap-2">
                                <input id="{{ $platform['key'] }}_callback_url" type="text" readonly value="{{ route('larapost.callback', ['platform' => $platform['key']]) }}" class="w-full rounded-lg border border-slate-300 bg-slate-50 px-2.5 py-2 text-xs text-slate-700">
                                <button type="button" data-copy-target="{{ $platform['key'] }}_callback_url" class="rounded-lg border border-slate-300 bg-white px-3 py-2 text-xs font-semibold text-slate-700 transition hover:bg-slate-50">Copy</button>
                            </div>
                            <a href="{{ $consoleUrl }}" target="_blank" rel="noopener" class="mt-2 inline-block text-xs font-semibold text-brand-700 hover:text-brand-600">Open {{ $platformLabel }} developer console</a>
                        </div>

                        <form method="POST" action="{{ route('larapost.settings.store', ['platform' => $platform['key']]) }}" class="mt-3 space-y-2.5">
                            @csrf
                            @foreach ($platform['fields'] as $field => $meta)
                                @php
                                    $isSecret = str_contains($field, 'secret');
                                    $value = old($field, $isSecret ? '' : ($platform['saved_credentials'][$field] ?? ''));
                                @endphp
                                <div>
                                    <label for="{{ $platform['key'] }}_{{ $field }}" class="mb-1 block text-sm font-medium text-slate-700">{{ $meta['label'] }}</label>
                                    <input
                                        id="{{ $platform['key'] }}_{{ $field }}"
                                        name="{{ $field }}"
                                        type="{{ $isSecret ? 'password' : 'text' }}"
                                        value="{{ $value }}"
                                        placeholder="{{ $isSecret && isset($platform['saved_credentials'][$field]) ? 'Saved (enter to replace)' : '' }}"
                                        class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm text-slate-800 outline-none transition focus:border-brand-500 focus:ring-4 focus:ring-brand-100"
                                    >
                                </div>
                            @endforeach

                            <button class="inline-flex rounded-xl bg-brand-500 px-4 py-2 text-sm font-semibold text-white transition hover:bg-brand-600" type="submit">Save</button>
                        </form>

                        <form method="POST" action="{{ route('larapost.settings.destroy', ['platform' => $platform['key']]) }}" class="mt-2">
                            @csrf
                            @method('DELETE')
                            <button class="inline-flex rounded-xl border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 transition hover:bg-slate-100" type="submit">Clear Saved Credentials</button>
                        </form>
                    </article>
                @endforeach
            </div>
        </section>

        <div class="grid gap-6 xl:grid-cols-[1.2fr_0.8fr]">
            <div class="space-y-6">
                <section class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
                    @php
                        $selectedPlatformValues = collect(old('platforms', []))->map(fn ($platform) => (string) $platform)->all();
                        $selectedAccountValues = collect(old('account_ids', []))->map(fn ($id) => (string) $id)->all();
                        $activeAccountList = $accounts->where('is_active', true)->values();
                        $activeAccounts = $activeAccountList->groupBy('platform');
                        $bulkEntries = collect(old('entries', [[
                            'account_id' => '',
                            'content' => '',
                            'media_url' => '',
                            'media_type' => 'image',
                            'schedule_for' => '',
                        ]]))
                            ->map(function ($entry): array {
                                $entry = is_array($entry) ? $entry : [];

                                return [
                                    'account_id' => (string) ($entry['account_id'] ?? ''),
                                    'content' => (string) ($entry['content'] ?? ''),
                                    'media_url' => (string) ($entry['media_url'] ?? ''),
                                    'media_type' => (string) ($entry['media_type'] ?? 'image'),
                                    'schedule_for' => (string) ($entry['schedule_for'] ?? ''),
                                ];
                            })
                            ->values();

                        if ($bulkEntries->isEmpty()) {
                            $bulkEntries = collect([[
                                'account_id' => '',
                                'content' => '',
                                'media_url' => '',
                                'media_type' => 'image',
                                'schedule_for' => '',
                            ]]);
                        }
                    @endphp

                    <h2 class="font-display text-2xl font-semibold text-slate-900">Publish / Schedule</h2>
                    <p class="mt-1 text-sm text-slate-600">Publish instantly or schedule for later. Leave account selection empty to target every active Facebook Page, Twitter / X account, or LinkedIn profile on the selected platforms. All schedule times use {{ $dashboardTimezone }}.</p>

                    <form method="POST" action="{{ route('larapost.publish') }}" class="mt-4 space-y-4">
                        @csrf
                        <div>
                            <label for="content" class="mb-1 block text-sm font-medium text-slate-700">Content</label>
                            <textarea id="content" name="content" required class="min-h-[140px] w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm text-slate-800 outline-none transition focus:border-brand-500 focus:ring-4 focus:ring-brand-100">{{ old('content') }}</textarea>
                        </div>

                        <div>
                            <label class="mb-2 block text-sm font-medium text-slate-700">Platforms</label>
                            <div class="flex flex-wrap gap-2">
                                @foreach ($platforms as $platform)
                                    @php
                                        $label = match ($platform['key']) {
                                            'facebook' => 'Facebook Pages',
                                            'twitter' => 'Twitter / X',
                                            'linkedin' => 'LinkedIn Profiles',
                                            default => ucfirst($platform['key']),
                                        };
                                    @endphp
                                    <label class="inline-flex items-center gap-2 rounded-lg border border-slate-300 bg-slate-50 px-3 py-2 text-sm text-slate-700">
                                        <input type="checkbox" class="h-4 w-4 rounded border-slate-300 text-brand-600 focus:ring-brand-500" data-platform-toggle="1" name="platforms[]" value="{{ $platform['key'] }}" {{ in_array($platform['key'], $selectedPlatformValues, true) ? 'checked' : '' }}>
                                        {{ $label }}
                                    </label>
                                @endforeach
                            </div>
                        </div>

                        <div>
                            <div class="mb-2 flex items-center justify-between gap-3">
                                <label class="block text-sm font-medium text-slate-700">Specific Accounts (optional)</label>
                                <span class="text-xs text-slate-500">Select exact Pages or accounts when you do not want to post to every active account on a platform.</span>
                            </div>

                            @if ($activeAccounts->isEmpty())
                                <div class="rounded-2xl border border-dashed border-slate-300 bg-slate-50 px-4 py-5 text-sm text-slate-500">
                                    Connect an account first. Once connected, it will appear here for targeted publishing and scheduling.
                                </div>
                            @else
                                <div class="grid gap-3 md:grid-cols-2">
                                    @foreach ($activeAccounts as $platformKey => $platformAccounts)
                                        <div class="rounded-2xl border border-slate-200 bg-slate-50/70 p-4">
                                            <div class="mb-3 flex items-center justify-between gap-2">
                                                <h3 class="font-display text-lg font-semibold text-slate-900">
                                                    {{ match ($platformKey) { 'facebook' => 'Facebook Pages', 'twitter' => 'Twitter / X', 'linkedin' => 'LinkedIn Profiles', default => ucfirst($platformKey) } }}
                                                </h3>
                                                <span class="rounded-full border border-slate-300 bg-white px-2 py-1 text-xs font-semibold text-slate-600">{{ $platformAccounts->count() }} active</span>
                                            </div>
                                            <div class="space-y-2">
                                                @foreach ($platformAccounts as $account)
                                                    <label class="flex items-start gap-3 rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm text-slate-700 transition hover:border-slate-300 hover:bg-slate-50">
                                                        <input
                                                            type="checkbox"
                                                            class="mt-1 h-4 w-4 rounded border-slate-300 text-brand-600 focus:ring-brand-500"
                                                            data-account-platform="{{ $platformKey }}"
                                                            name="account_ids[]"
                                                            value="{{ $account->id }}"
                                                            {{ in_array((string) $account->id, $selectedAccountValues, true) ? 'checked' : '' }}
                                                        >
                                                        <span class="min-w-0 flex-1">
                                                            <span class="block font-semibold text-slate-900">{{ $account->account_name }}</span>
                                                            <span class="block text-xs text-slate-500">{{ $account->account_username ?: $account->account_id_on_platform }}</span>
                                                        </span>
                                                    </label>
                                                @endforeach
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            @endif
                        </div>

                        <div class="grid gap-4 sm:grid-cols-2">
                            <div>
                                <label for="media_url" class="mb-1 block text-sm font-medium text-slate-700">Media URL (optional)</label>
                                <input id="media_url" name="media_url" type="url" value="{{ old('media_url') }}" placeholder="https://cdn.example.com/image.jpg" class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm text-slate-800 outline-none transition focus:border-brand-500 focus:ring-4 focus:ring-brand-100">
                                <p class="mt-1 text-xs text-slate-500">Facebook Page image and video URLs are the verified path here. LinkedIn media uploads require a local file path in code, and X media upload is not handled by this dashboard flow.</p>
                            </div>
                            <div>
                                <label for="media_type" class="mb-1 block text-sm font-medium text-slate-700">Media Type</label>
                                <select id="media_type" name="media_type" class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm text-slate-800 outline-none transition focus:border-brand-500 focus:ring-4 focus:ring-brand-100">
                                    <option value="image" {{ old('media_type', 'image') === 'image' ? 'selected' : '' }}>Image</option>
                                    <option value="video" {{ old('media_type') === 'video' ? 'selected' : '' }}>Video</option>
                                </select>
                            </div>
                        </div>

                        <div>
                            <label for="schedule_for" class="mb-1 block text-sm font-medium text-slate-700">Schedule For (optional)</label>
                            <input id="schedule_for" name="schedule_for" type="datetime-local" value="{{ old('schedule_for') }}" class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm text-slate-800 outline-none transition focus:border-brand-500 focus:ring-4 focus:ring-brand-100">
                            <p class="mt-1 text-xs text-slate-500">This time is interpreted in {{ $dashboardTimezone }}.</p>
                        </div>

                        <button class="inline-flex rounded-xl bg-brand-500 px-4 py-2 text-sm font-semibold text-white transition hover:bg-brand-600" type="submit">Publish / Schedule</button>
                    </form>
                </section>

                <section class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
                    <div class="flex flex-wrap items-start justify-between gap-3">
                        <div>
                            <h2 class="font-display text-2xl font-semibold text-slate-900">Bulk Composer</h2>
                            <p class="mt-1 max-w-3xl text-sm text-slate-600">Create multiple rows in one submit. Each row can target a different connected Page or account with its own content, media, and optional schedule in {{ $dashboardTimezone }}.</p>
                        </div>
                        <button type="button" data-bulk-add-row class="inline-flex items-center rounded-xl border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 transition hover:border-slate-400 hover:bg-slate-50">Add Row</button>
                    </div>

                    @if ($activeAccountList->isEmpty())
                        <div class="mt-4 rounded-2xl border border-dashed border-slate-300 bg-slate-50 px-4 py-5 text-sm text-slate-500">
                            Connect at least one active account to use the bulk composer.
                        </div>
                    @else
                        <form method="POST" action="{{ route('larapost.publish.bulk') }}" class="mt-4 space-y-4">
                            @csrf
                            <div id="bulk-composer-rows" class="space-y-4">
                                @foreach ($bulkEntries as $index => $entry)
                                    <article class="rounded-2xl border border-slate-200 bg-slate-50/70 p-4" data-bulk-row data-bulk-index="{{ $index }}">
                                        <div class="mb-4 flex items-center justify-between gap-3">
                                            <div>
                                                <p class="text-xs font-semibold uppercase tracking-[0.18em] text-brand-600" data-bulk-label>Row {{ $index + 1 }}</p>
                                                <p class="mt-1 text-sm text-slate-500">Target one account with its own message.</p>
                                            </div>
                                            <button type="button" data-bulk-remove class="inline-flex items-center rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm font-semibold text-slate-700 transition hover:border-slate-400 hover:bg-slate-50">Remove</button>
                                        </div>

                                        <div class="grid gap-4 md:grid-cols-2">
                                            <div>
                                                <label class="mb-1 block text-sm font-medium text-slate-700" for="bulk_account_{{ $index }}">Connected Account</label>
                                                <select id="bulk_account_{{ $index }}" name="entries[{{ $index }}][account_id]" required class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm text-slate-800 outline-none transition focus:border-brand-500 focus:ring-4 focus:ring-brand-100">
                                                    <option value="">Select an account</option>
                                                    @foreach ($activeAccountList as $account)
                                                        @php
                                                            $accountPlatformLabel = match ($account->platform) {
                                                                'facebook' => 'Facebook Pages',
                                                                'twitter' => 'Twitter / X',
                                                                'linkedin' => 'LinkedIn Profiles',
                                                                default => ucfirst($account->platform),
                                                            };
                                                        @endphp
                                                        <option value="{{ $account->id }}" {{ $entry['account_id'] === (string) $account->id ? 'selected' : '' }}>{{ $accountPlatformLabel }} · {{ $account->account_name }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            <div>
                                                <label class="mb-1 block text-sm font-medium text-slate-700" for="bulk_schedule_{{ $index }}">Schedule For (optional)</label>
                                                <input id="bulk_schedule_{{ $index }}" name="entries[{{ $index }}][schedule_for]" type="datetime-local" value="{{ $entry['schedule_for'] }}" class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm text-slate-800 outline-none transition focus:border-brand-500 focus:ring-4 focus:ring-brand-100">
                                            </div>
                                        </div>

                                        <div class="mt-4">
                                            <label class="mb-1 block text-sm font-medium text-slate-700" for="bulk_content_{{ $index }}">Content</label>
                                            <textarea id="bulk_content_{{ $index }}" name="entries[{{ $index }}][content]" required class="min-h-[140px] w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm text-slate-800 outline-none transition focus:border-brand-500 focus:ring-4 focus:ring-brand-100">{{ $entry['content'] }}</textarea>
                                        </div>

                                        <div class="mt-4 grid gap-4 md:grid-cols-[1fr_220px]">
                                            <div>
                                                <label class="mb-1 block text-sm font-medium text-slate-700" for="bulk_media_url_{{ $index }}">Media URL (optional)</label>
                                                <input id="bulk_media_url_{{ $index }}" name="entries[{{ $index }}][media_url]" type="url" value="{{ $entry['media_url'] }}" placeholder="https://cdn.example.com/image.jpg" class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm text-slate-800 outline-none transition focus:border-brand-500 focus:ring-4 focus:ring-brand-100">
                                            </div>
                                            <div>
                                                <label class="mb-1 block text-sm font-medium text-slate-700" for="bulk_media_type_{{ $index }}">Media Type</label>
                                                <select id="bulk_media_type_{{ $index }}" name="entries[{{ $index }}][media_type]" class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm text-slate-800 outline-none transition focus:border-brand-500 focus:ring-4 focus:ring-brand-100">
                                                    <option value="image" {{ $entry['media_type'] === 'video' ? '' : 'selected' }}>Image</option>
                                                    <option value="video" {{ $entry['media_type'] === 'video' ? 'selected' : '' }}>Video</option>
                                                </select>
                                            </div>
                                        </div>
                                    </article>
                                @endforeach
                            </div>

                            <template id="bulk-composer-template">
                                <article class="rounded-2xl border border-slate-200 bg-slate-50/70 p-4" data-bulk-row data-bulk-index="__INDEX__">
                                    <div class="mb-4 flex items-center justify-between gap-3">
                                        <div>
                                            <p class="text-xs font-semibold uppercase tracking-[0.18em] text-brand-600" data-bulk-label>Row __LABEL__</p>
                                            <p class="mt-1 text-sm text-slate-500">Target one account with its own message.</p>
                                        </div>
                                        <button type="button" data-bulk-remove class="inline-flex items-center rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm font-semibold text-slate-700 transition hover:border-slate-400 hover:bg-slate-50">Remove</button>
                                    </div>

                                    <div class="grid gap-4 md:grid-cols-2">
                                        <div>
                                            <label class="mb-1 block text-sm font-medium text-slate-700" for="bulk_account___INDEX__">Connected Account</label>
                                            <select id="bulk_account___INDEX__" name="entries[__INDEX__][account_id]" required class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm text-slate-800 outline-none transition focus:border-brand-500 focus:ring-4 focus:ring-brand-100">
                                                <option value="">Select an account</option>
                                                @foreach ($activeAccountList as $account)
                                                    @php
                                                        $accountPlatformLabel = match ($account->platform) {
                                                            'twitter' => 'Twitter / X',
                                                            'linkedin' => 'LinkedIn',
                                                            default => ucfirst($account->platform),
                                                        };
                                                    @endphp
                                                    <option value="{{ $account->id }}">{{ $accountPlatformLabel }} · {{ $account->account_name }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div>
                                            <label class="mb-1 block text-sm font-medium text-slate-700" for="bulk_schedule___INDEX__">Schedule For (optional)</label>
                                            <input id="bulk_schedule___INDEX__" name="entries[__INDEX__][schedule_for]" type="datetime-local" class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm text-slate-800 outline-none transition focus:border-brand-500 focus:ring-4 focus:ring-brand-100">
                                        </div>
                                    </div>

                                    <div class="mt-4">
                                        <label class="mb-1 block text-sm font-medium text-slate-700" for="bulk_content___INDEX__">Content</label>
                                        <textarea id="bulk_content___INDEX__" name="entries[__INDEX__][content]" required class="min-h-[140px] w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm text-slate-800 outline-none transition focus:border-brand-500 focus:ring-4 focus:ring-brand-100"></textarea>
                                    </div>

                                    <div class="mt-4 grid gap-4 md:grid-cols-[1fr_220px]">
                                        <div>
                                            <label class="mb-1 block text-sm font-medium text-slate-700" for="bulk_media_url___INDEX__">Media URL (optional)</label>
                                            <input id="bulk_media_url___INDEX__" name="entries[__INDEX__][media_url]" type="url" placeholder="https://cdn.example.com/image.jpg" class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm text-slate-800 outline-none transition focus:border-brand-500 focus:ring-4 focus:ring-brand-100">
                                        </div>
                                        <div>
                                            <label class="mb-1 block text-sm font-medium text-slate-700" for="bulk_media_type___INDEX__">Media Type</label>
                                            <select id="bulk_media_type___INDEX__" name="entries[__INDEX__][media_type]" class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm text-slate-800 outline-none transition focus:border-brand-500 focus:ring-4 focus:ring-brand-100">
                                                <option value="image" selected>Image</option>
                                                <option value="video">Video</option>
                                            </select>
                                        </div>
                                    </div>
                                </article>
                            </template>

                            <div class="flex flex-wrap items-center gap-3">
                                <button type="button" data-bulk-add-row class="inline-flex items-center rounded-xl border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 transition hover:border-slate-400 hover:bg-slate-50">Add Row</button>
                                <button class="inline-flex rounded-xl bg-brand-500 px-4 py-2 text-sm font-semibold text-white transition hover:bg-brand-600" type="submit">Publish / Schedule All Rows</button>
                            </div>
                        </form>
                    @endif
                </section>
            </div>

            <section class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
                <h2 class="font-display text-2xl font-semibold text-slate-900">Connected Accounts</h2>
                <p class="mt-1 text-sm text-slate-600">Enable or disable accounts for publishing.</p>

                <div class="mt-4 overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200 text-sm">
                        <thead>
                        <tr class="text-left text-xs uppercase tracking-wide text-slate-500">
                            <th class="pb-2 pr-3">Platform</th>
                            <th class="pb-2 pr-3">Account</th>
                            <th class="pb-2 pr-3">Status</th>
                            <th class="pb-2">Action</th>
                        </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                        @forelse ($accounts as $account)
                            <tr>
                                <td class="py-2 pr-3">{{ match ($account->platform) { 'facebook' => 'Facebook Pages', 'twitter' => 'Twitter / X', 'linkedin' => 'LinkedIn Profiles', default => ucfirst($account->platform) } }}</td>
                                <td class="py-2 pr-3">
                                    <div class="font-medium text-slate-800">{{ $account->account_name }}</div>
                                    <div class="text-xs text-slate-500">{{ $account->account_username ?: $account->account_id_on_platform }}</div>
                                </td>
                                <td class="py-2 pr-3">{{ $account->is_active ? 'Active' : 'Inactive' }}</td>
                                <td class="py-2">
                                    <form method="POST" action="{{ route('larapost.accounts.toggle', ['account' => $account->id]) }}">
                                        @csrf
                                        <button class="rounded-lg border border-slate-300 bg-white px-3 py-1.5 text-xs font-semibold text-slate-700 transition hover:bg-slate-50" type="submit">{{ $account->is_active ? 'Disable' : 'Enable' }}</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="py-4 text-slate-500">No connected accounts yet.</td>
                            </tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </section>
        </div>

        <section class="mt-6 rounded-3xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
            <div class="flex flex-wrap items-start justify-between gap-3">
                <div>
                    <h2 class="font-display text-2xl font-semibold text-slate-900">Recent Posts</h2>
                    <p class="mt-1 text-sm text-slate-600">Times below are shown in {{ $dashboardTimezone }}.</p>
                </div>
            </div>
            <div class="mt-4 overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 text-sm">
                    <thead>
                    <tr class="text-left text-xs uppercase tracking-wide text-slate-500">
                        <th class="pb-2 pr-3">ID</th>
                        <th class="pb-2 pr-3">Platform</th>
                        <th class="pb-2 pr-3">Status</th>
                        <th class="pb-2 pr-3">When</th>
                        <th class="pb-2">Content</th>
                    </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                    @forelse ($recentPosts as $post)
                        @php
                            $statusClass = match ($post->status) {
                                'published' => 'bg-emerald-100 text-emerald-700 border-emerald-200',
                                'failed' => 'bg-rose-100 text-rose-700 border-rose-200',
                                'pending', 'processing' => 'bg-amber-100 text-amber-700 border-amber-200',
                                default => 'bg-slate-100 text-slate-700 border-slate-200',
                            };
                        @endphp
                        <tr>
                            <td class="py-2 pr-3">#{{ $post->id }}</td>
                            <td class="py-2 pr-3">{{ ucfirst($post->account->platform ?? 'n/a') }}</td>
                            <td class="py-2 pr-3">
                                <span class="inline-flex rounded-full border px-2 py-1 text-xs font-semibold {{ $statusClass }}">{{ strtoupper($post->status) }}</span>
                            </td>
                            <td class="py-2 pr-3 text-xs text-slate-600">
                                @if ($post->scheduled_for)
                                    Scheduled: {{ $post->scheduled_for->timezone($dashboardTimezone)->format('Y-m-d H:i') }}<br>
                                @endif
                                Created: {{ $post->created_at?->timezone($dashboardTimezone)->format('Y-m-d H:i') }}
                            </td>
                            <td class="py-2">{{ \Illuminate\Support\Str::limit($post->content, 120) }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="py-4 text-slate-500">No posts created yet.</td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    </main>

    <script>
        (function () {
            var links = document.querySelectorAll('[data-larapost-oauth-popup="1"]');
            var copyButtons = document.querySelectorAll('[data-copy-target]');
            var platformInputs = document.querySelectorAll('[data-platform-toggle]');
            var accountInputs = document.querySelectorAll('[data-account-platform]');
            var bulkRows = document.getElementById('bulk-composer-rows');
            var bulkTemplate = document.getElementById('bulk-composer-template');
            var bulkAddButtons = document.querySelectorAll('[data-bulk-add-row]');
            var bulkRowIndex = bulkRows ? Array.prototype.reduce.call(bulkRows.querySelectorAll('[data-bulk-row]'), function (highest, row) {
                var value = parseInt(row.getAttribute('data-bulk-index') || '-1', 10);
                return Math.max(highest, isNaN(value) ? -1 : value);
            }, -1) + 1 : 0;

            function popupFeatures(width, height) {
                var left = Math.max(0, Math.round((window.screen.width - width) / 2));
                var top = Math.max(0, Math.round((window.screen.height - height) / 2));
                return 'popup=yes,width=' + width + ',height=' + height + ',left=' + left + ',top=' + top + ',resizable=yes,scrollbars=yes';
            }

            function platformInputFor(platform) {
                return document.querySelector('[data-platform-toggle][value="' + platform + '"]');
            }

            function syncBulkComposer() {
                if (!bulkRows) {
                    return;
                }

                var rows = bulkRows.querySelectorAll('[data-bulk-row]');
                var removable = rows.length > 1;

                rows.forEach(function (row, index) {
                    var label = row.querySelector('[data-bulk-label]');
                    var removeButton = row.querySelector('[data-bulk-remove]');

                    if (label) {
                        label.textContent = 'Row ' + (index + 1);
                    }

                    if (removeButton) {
                        removeButton.hidden = !removable;
                    }
                });
            }

            copyButtons.forEach(function (button) {
                button.addEventListener('click', function () {
                    var targetId = button.getAttribute('data-copy-target');
                    var input = document.getElementById(targetId);

                    if (!input) {
                        return;
                    }

                    var text = input.value;
                    var original = button.textContent;

                    if (navigator.clipboard && navigator.clipboard.writeText) {
                        navigator.clipboard.writeText(text).then(function () {
                            button.textContent = 'Copied';
                            setTimeout(function () {
                                button.textContent = original;
                            }, 1200);
                        });

                        return;
                    }

                    input.removeAttribute('readonly');
                    input.select();
                    document.execCommand('copy');
                    input.setAttribute('readonly', 'readonly');
                    button.textContent = 'Copied';
                    setTimeout(function () {
                        button.textContent = original;
                    }, 1200);
                });
            });

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

            platformInputs.forEach(function (input) {
                input.addEventListener('change', function () {
                    if (input.checked) {
                        return;
                    }

                    accountInputs.forEach(function (accountInput) {
                        if (accountInput.getAttribute('data-account-platform') === input.value) {
                            accountInput.checked = false;
                        }
                    });
                });
            });

            accountInputs.forEach(function (input) {
                input.addEventListener('change', function () {
                    if (!input.checked) {
                        return;
                    }

                    var platform = input.getAttribute('data-account-platform');
                    var platformInput = platformInputFor(platform);

                    if (platformInput) {
                        platformInput.checked = true;
                    }
                });
            });

            if (bulkRows && bulkTemplate) {
                bulkAddButtons.forEach(function (button) {
                    button.addEventListener('click', function () {
                        var nextIndex = bulkRowIndex++;
                        var nextLabel = bulkRows.querySelectorAll('[data-bulk-row]').length + 1;
                        var html = bulkTemplate.innerHTML
                            .replace(/__INDEX__/g, String(nextIndex))
                            .replace(/__LABEL__/g, String(nextLabel))
                            .trim();

                        bulkRows.insertAdjacentHTML('beforeend', html);
                        syncBulkComposer();
                    });
                });

                bulkRows.addEventListener('click', function (event) {
                    var button = event.target.closest('[data-bulk-remove]');

                    if (!button) {
                        return;
                    }

                    var row = button.closest('[data-bulk-row]');

                    if (!row) {
                        return;
                    }

                    if (bulkRows.querySelectorAll('[data-bulk-row]').length === 1) {
                        return;
                    }

                    row.remove();
                    syncBulkComposer();
                });

                syncBulkComposer();
            }

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
