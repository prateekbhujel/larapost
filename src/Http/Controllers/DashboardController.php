<?php

namespace SocialSync\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use SocialSync\Facades\SocialMedia;
use SocialSync\Models\PlatformCredential;
use SocialSync\Models\ScheduledPost;
use SocialSync\Models\SocialAccount;

class DashboardController extends Controller
{
    public function index(): View
    {
        $accounts = SocialAccount::query()->orderBy('platform')->orderBy('account_name')->get();
        $recentPosts = ScheduledPost::query()->with('account')->latest()->limit(20)->get();

        $platforms = collect(SocialMedia::supportedPlatforms())
            ->map(fn (string $platform): array => $this->platformState($platform))
            ->values();

        return view('larapost::dashboard', [
            'platforms' => $platforms,
            'accounts' => $accounts,
            'recentPosts' => $recentPosts,
            'publishResults' => session('larapost_results', []),
        ]);
    }

    public function storeCredentials(Request $request, string $platform): RedirectResponse
    {
        $platform = $this->assertSupportedPlatform($platform);
        $fields = $this->platformFields($platform);

        $rules = [];

        foreach ($fields as $field => $meta) {
            $rules[$field] = ['nullable', 'string', 'max:' . ($meta['max'] ?? 2048)];
        }

        $validated = $request->validate($rules);

        $credentials = collect($validated)
            ->mapWithKeys(fn ($value, $key) => [$key => is_string($value) ? trim($value) : $value])
            ->filter(fn ($value) => $value !== null && $value !== '')
            ->all();

        if ($credentials === []) {
            PlatformCredential::query()->platform($platform)->delete();
            SocialMedia::forgetDriver($platform);

            return back()->with('success', ucfirst($platform) . ' credentials were cleared.');
        }

        PlatformCredential::query()->updateOrCreate(
            ['platform' => $platform],
            ['credentials' => $credentials]
        );

        SocialMedia::forgetDriver($platform);

        return back()->with('success', ucfirst($platform) . ' credentials were saved successfully.');
    }

    public function destroyCredentials(string $platform): RedirectResponse
    {
        $platform = $this->assertSupportedPlatform($platform);

        PlatformCredential::query()->platform($platform)->delete();
        SocialMedia::forgetDriver($platform);

        return back()->with('success', ucfirst($platform) . ' credentials were removed.');
    }

    public function publish(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'content' => ['required', 'string', 'max:5000'],
            'platforms' => ['nullable', 'array'],
            'platforms.*' => ['required', Rule::in(SocialMedia::supportedPlatforms())],
            'account_ids' => ['nullable', 'array'],
            'account_ids.*' => ['required', 'integer', Rule::exists('social_accounts', 'id')],
            'media_url' => ['nullable', 'url', 'max:2048'],
            'media_type' => ['nullable', Rule::in(['image', 'video'])],
            'schedule_for' => ['nullable', 'date_format:Y-m-d\TH:i'],
        ]);

        try {
            $selectedAccounts = SocialAccount::query()
                ->active()
                ->when(
                    !empty($validated['account_ids']),
                    fn ($query) => $query->whereIn('id', array_map('intval', $validated['account_ids']))
                )
                ->get();

            if (!empty($validated['account_ids']) && $selectedAccounts->isEmpty()) {
                throw ValidationException::withMessages([
                    'account_ids' => 'No active connected accounts matched the selected account list.',
                ]);
            }

            $platforms = collect($validated['platforms'] ?? [])
                ->map(static fn ($platform) => strtolower(trim((string) $platform)))
                ->filter()
                ->unique()
                ->values();

            if ($platforms->isEmpty() && $selectedAccounts->isNotEmpty()) {
                $platforms = $selectedAccounts->pluck('platform')->unique()->values();
            }

            if ($platforms->isEmpty()) {
                throw ValidationException::withMessages([
                    'platforms' => 'Select at least one platform or one connected account.',
                ]);
            }

            $builder = SocialMedia::post()
                ->content($validated['content'])
                ->platforms($platforms->all());

            if ($selectedAccounts->isNotEmpty()) {
                $builder->accounts(
                    $selectedAccounts
                        ->groupBy('platform')
                        ->map(fn ($accounts) => $accounts->pluck('id')->map(static fn ($id) => (int) $id)->values()->all())
                        ->all()
                );
            }

            $mediaUrl = trim((string) ($validated['media_url'] ?? ''));
            if ($mediaUrl !== '') {
                $mediaType = (string) ($validated['media_type'] ?? 'image');

                if ($mediaType === 'video') {
                    $builder->video($mediaUrl);
                } else {
                    $builder->image($mediaUrl);
                }
            }

            if (!empty($validated['schedule_for'])) {
                $scheduled = Carbon::createFromFormat('Y-m-d\TH:i', (string) $validated['schedule_for'], config('app.timezone'));
                $builder->scheduleFor($scheduled);
            }

            $results = $builder->publish();

            $total = count($results);
            $success = collect($results)->where('success', true)->count();
            $scheduled = collect($results)->where('scheduled', true)->count();
            $failed = max(0, $total - $success);

            $message = $scheduled > 0
                ? sprintf('Scheduled %d post(s) across %d account(s).', $scheduled, $total)
                : sprintf('Published %d/%d account(s).', $success, $total);

            if ($failed > 0) {
                $message .= sprintf(' %d failed.', $failed);
            }

            return back()
                ->with('success', $message)
                ->with('larapost_results', $results);
        } catch (\Throwable $exception) {
            return back()
                ->withInput()
                ->with('error', $exception->getMessage());
        }
    }

    public function toggleAccount(SocialAccount $account): RedirectResponse
    {
        $account->forceFill([
            'is_active' => !$account->is_active,
        ])->save();

        return back()->with('success', sprintf(
            '%s account "%s" is now %s.',
            ucfirst($account->platform),
            $account->account_name,
            $account->is_active ? 'active' : 'inactive'
        ));
    }

    protected function assertSupportedPlatform(string $platform): string
    {
        $platform = strtolower($platform);

        if (!in_array($platform, SocialMedia::supportedPlatforms(), true)) {
            abort(404, 'Unsupported platform.');
        }

        return $platform;
    }

    /**
     * @return array<string, array{label: string, max?: int}>
     */
    protected function platformFields(string $platform): array
    {
        return match ($platform) {
            'facebook', 'instagram' => [
                'app_id' => ['label' => 'App ID'],
                'app_secret' => ['label' => 'App Secret'],
                'api_version' => ['label' => 'API Version', 'max' => 64],
            ],
            'twitter' => [
                'client_id' => ['label' => 'Client ID'],
                'client_secret' => ['label' => 'Client Secret'],
                'api_version' => ['label' => 'API Version', 'max' => 16],
            ],
            'linkedin' => [
                'client_id' => ['label' => 'Client ID'],
                'client_secret' => ['label' => 'Client Secret'],
            ],
            default => [],
        };
    }

    /**
     * @return array<int, string>
     */
    protected function requiredFields(string $platform): array
    {
        return match ($platform) {
            'facebook', 'instagram' => ['app_id', 'app_secret'],
            'twitter', 'linkedin' => ['client_id', 'client_secret'],
            default => [],
        };
    }

    /**
     * @return array<string, mixed>
     */
    protected function platformState(string $platform): array
    {
        $saved = PlatformCredential::query()->platform($platform)->first();
        $savedCredentials = is_array($saved?->credentials) ? $saved->credentials : [];

        $effective = SocialMedia::platformConfig($platform);
        $required = $this->requiredFields($platform);

        $configured = collect($required)->every(fn (string $key): bool => filled($effective[$key] ?? null));

        return [
            'key' => $platform,
            'label' => ucfirst($platform),
            'fields' => $this->platformFields($platform),
            'required_fields' => $required,
            'saved_credentials' => $savedCredentials,
            'effective_credentials' => $effective,
            'configured' => $configured,
            'source' => $saved ? 'database' : 'env',
            'active_accounts' => SocialAccount::query()->active()->where('platform', $platform)->count(),
            'total_accounts' => SocialAccount::query()->where('platform', $platform)->count(),
        ];
    }
}
