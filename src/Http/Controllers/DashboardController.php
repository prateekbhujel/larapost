<?php

namespace SocialSync\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use SocialSync\Facades\SocialMedia;
use SocialSync\Models\PlatformCredential;
use SocialSync\Models\ScheduledPost;
use SocialSync\Models\SocialAccount;
use SocialSync\PostBuilder;

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
            $selectedAccounts = $this->resolveSelectedAccounts($validated['account_ids'] ?? []);
            $platforms = $this->resolvePlatforms($validated['platforms'] ?? [], $selectedAccounts);

            $builder = SocialMedia::post()
                ->content($validated['content'])
                ->platforms($platforms);

            if ($selectedAccounts->isNotEmpty()) {
                $builder->accounts($this->accountFilters($selectedAccounts));
            }

            $this->applyMediaToBuilder(
                $builder,
                (string) ($validated['media_url'] ?? ''),
                (string) ($validated['media_type'] ?? 'image')
            );

            if (!empty($validated['schedule_for'])) {
                $builder->scheduleFor($this->parseSchedule((string) $validated['schedule_for']));
            }

            return $this->redirectWithResults($builder->publish(), null);
        } catch (ValidationException $exception) {
            throw $exception;
        } catch (\Throwable $exception) {
            return back()
                ->withInput()
                ->with('error', $exception->getMessage());
        }
    }

    public function publishBulk(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'entries' => ['required', 'array', 'min:1'],
            'entries.*.account_id' => ['required', 'integer', Rule::exists('social_accounts', 'id')],
            'entries.*.content' => ['required', 'string', 'max:5000'],
            'entries.*.media_url' => ['nullable', 'url', 'max:2048'],
            'entries.*.media_type' => ['nullable', Rule::in(['image', 'video'])],
            'entries.*.schedule_for' => ['nullable', 'date_format:Y-m-d\TH:i'],
        ]);

        try {
            $entries = collect($validated['entries'])
                ->map(fn (array $entry): array => [
                    'account_id' => (int) $entry['account_id'],
                    'content' => trim((string) $entry['content']),
                    'media_url' => trim((string) ($entry['media_url'] ?? '')),
                    'media_type' => (string) ($entry['media_type'] ?? 'image'),
                    'schedule_for' => (string) ($entry['schedule_for'] ?? ''),
                ])
                ->values();

            $accounts = SocialAccount::query()
                ->active()
                ->whereIn('id', $entries->pluck('account_id')->unique()->all())
                ->get()
                ->keyBy('id');

            $results = [];

            foreach ($entries as $index => $entry) {
                $account = $accounts->get($entry['account_id']);

                if (!$account) {
                    throw ValidationException::withMessages([
                        sprintf('entries.%d.account_id', $index) => 'Selected account is missing or inactive.',
                    ]);
                }

                $builder = SocialMedia::post()
                    ->content($entry['content'])
                    ->platform($account->platform)
                    ->accounts([$account->platform => [$account->id]]);

                $this->applyMediaToBuilder($builder, $entry['media_url'], $entry['media_type']);

                if ($entry['schedule_for'] !== '') {
                    $builder->scheduleFor($this->parseSchedule($entry['schedule_for']));
                }

                foreach ($builder->publish() as $result) {
                    $result['bulk_entry'] = $index;
                    $results[] = $result;
                }
            }

            return $this->redirectWithResults($results, $entries->count());
        } catch (ValidationException $exception) {
            throw $exception;
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

    protected function resolveSelectedAccounts(array $accountIds): Collection
    {
        $ids = collect($accountIds)
            ->map(static fn ($id): int => (int) $id)
            ->filter(static fn (int $id): bool => $id > 0)
            ->unique()
            ->values();

        if ($ids->isEmpty()) {
            return collect();
        }

        $accounts = SocialAccount::query()
            ->active()
            ->whereIn('id', $ids->all())
            ->get();

        if ($accounts->isEmpty()) {
            throw ValidationException::withMessages([
                'account_ids' => 'No active connected accounts matched the selected account list.',
            ]);
        }

        return $accounts;
    }

    protected function resolvePlatforms(array $platforms, Collection $selectedAccounts): array
    {
        $normalized = collect($platforms)
            ->map(static fn ($platform): string => strtolower(trim((string) $platform)))
            ->filter()
            ->unique()
            ->values();

        if ($normalized->isEmpty() && $selectedAccounts->isNotEmpty()) {
            $normalized = $selectedAccounts->pluck('platform')->unique()->values();
        }

        if ($normalized->isEmpty()) {
            throw ValidationException::withMessages([
                'platforms' => 'Select at least one platform or one connected account.',
            ]);
        }

        return $normalized->all();
    }

    protected function accountFilters(Collection $accounts): array
    {
        return $accounts
            ->groupBy('platform')
            ->map(fn (Collection $platformAccounts): array => $platformAccounts->pluck('id')->map(static fn ($id): int => (int) $id)->values()->all())
            ->all();
    }

    protected function applyMediaToBuilder(PostBuilder $builder, string $mediaUrl, string $mediaType): void
    {
        $mediaUrl = trim($mediaUrl);

        if ($mediaUrl === '') {
            return;
        }

        if ($mediaType === 'video') {
            $builder->video($mediaUrl);

            return;
        }

        $builder->image($mediaUrl);
    }

    protected function parseSchedule(string $value): Carbon
    {
        return Carbon::createFromFormat('Y-m-d\TH:i', $value, config('app.timezone'));
    }

    protected function redirectWithResults(array $results, ?int $bulkRows): RedirectResponse
    {
        $total = count($results);
        $scheduled = collect($results)->where('scheduled', true)->count();
        $published = collect($results)->filter(fn (array $result): bool => ($result['success'] ?? false) === true && ($result['scheduled'] ?? false) !== true)->count();
        $failed = max(0, $total - $scheduled - $published);

        $message = $bulkRows !== null
            ? sprintf('Bulk composer processed %d row(s): %d published, %d scheduled, %d failed.', $bulkRows, $published, $scheduled, $failed)
            : ($scheduled > 0
                ? sprintf('Scheduled %d post(s) across %d account(s).', $scheduled, $total)
                : sprintf('Published %d/%d account(s).', $published, $total));

        if ($bulkRows === null && $failed > 0) {
            $message .= sprintf(' %d failed.', $failed);
        }

        return back()
            ->with('success', $message)
            ->with('larapost_results', $results);
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
