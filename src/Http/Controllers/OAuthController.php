<?php

namespace SocialSync\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use SocialSync\Facades\SocialMedia;
use SocialSync\Models\SocialAccount;
use SocialSync\Support\AccountDataResolver;

class OAuthController extends Controller
{
    public function connect(Request $request, string $platform): RedirectResponse|JsonResponse|Response
    {
        $popupMode = $this->isPopupModeRequest($request);

        try {
            $driver = SocialMedia::driver($platform);
            $callbackUrl = route('larapost.callback', ['platform' => $platform]);

            if ($popupMode) {
                $this->storePopupMode($platform);
            }

            return redirect()->away($driver->getAuthorizationUrl($callbackUrl));
        } catch (\Throwable $exception) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Failed to initiate OAuth flow.',
                    'error' => $exception->getMessage(),
                ], 422);
            }

            if ($popupMode) {
                return response()->view('larapost::oauth-error', [
                    'error' => $exception->getMessage(),
                    'platform' => $platform,
                ], 422);
            }

            return redirect()->route('larapost.dashboard')->with('error', $exception->getMessage());
        }
    }

    public function callback(Request $request, string $platform): RedirectResponse|JsonResponse|Response
    {
        $popupMode = $this->isPopupModeRequest($request) || $this->consumePopupMode($platform);

        try {
            if ($request->filled('error')) {
                throw new \RuntimeException((string) $request->input('error_description', $request->input('error')));
            }

            $code = (string) $request->input('code', '');

            if ($code === '') {
                throw new \RuntimeException('Missing OAuth authorization code.');
            }

            $driver = SocialMedia::driver($platform);
            $callbackUrl = route('larapost.callback', ['platform' => $platform]);
            $credentials = $driver->handleCallback($code, $callbackUrl);
            $accountData = AccountDataResolver::fromCredentials($platform, $credentials);

            $account = SocialAccount::query()->updateOrCreate(
                [
                    'platform' => $platform,
                    'account_id_on_platform' => $accountData['id'],
                ],
                [
                    'account_name' => $accountData['name'],
                    'account_username' => $accountData['username'],
                    'credentials' => $credentials,
                    'metadata' => $accountData['metadata'] ?? [],
                    'is_active' => true,
                ]
            );

            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Account connected successfully.',
                    'platform' => $platform,
                    'account_id' => $account->id,
                ]);
            }

            $message = sprintf('%s account "%s" connected successfully.', ucfirst($platform), $account->account_name);

            if ($popupMode) {
                return response()->view('larapost::oauth-success', [
                    'message' => $message,
                    'platform' => $platform,
                    'dashboardUrl' => route('larapost.dashboard'),
                ]);
            }

            return redirect()
                ->route('larapost.dashboard')
                ->with('success', $message);
        } catch (\Throwable $exception) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Account connection failed.',
                    'error' => $exception->getMessage(),
                ], 422);
            }

            if ($popupMode) {
                return response()->view('larapost::oauth-error', [
                    'error' => $exception->getMessage(),
                    'platform' => $platform,
                    'dashboardUrl' => route('larapost.dashboard'),
                ], 422);
            }

            return redirect()->route('larapost.dashboard')->with('error', $exception->getMessage());
        }
    }

    protected function isPopupModeRequest(Request $request): bool
    {
        return $request->boolean('popup') || $request->query('mode') === 'popup';
    }

    protected function popupModeSessionKey(string $platform): string
    {
        return 'larapost_oauth_popup_' . strtolower($platform);
    }

    protected function storePopupMode(string $platform): void
    {
        if (!function_exists('session')) {
            return;
        }

        session([$this->popupModeSessionKey($platform) => true]);
    }

    protected function consumePopupMode(string $platform): bool
    {
        if (!function_exists('session')) {
            return false;
        }

        $key = $this->popupModeSessionKey($platform);
        $popupMode = (bool) session($key, false);

        if ($popupMode) {
            session()->forget($key);
        }

        return $popupMode;
    }
}
