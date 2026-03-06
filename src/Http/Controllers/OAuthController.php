<?php

namespace SocialSync\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use SocialSync\Facades\SocialMedia;
use SocialSync\Models\SocialAccount;
use SocialSync\Support\AccountDataResolver;

class OAuthController extends Controller
{
    public function connect(Request $request, string $platform): RedirectResponse|JsonResponse
    {
        try {
            $driver = SocialMedia::driver($platform);
            $callbackUrl = route('larapost.callback', ['platform' => $platform]);

            return redirect()->away($driver->getAuthorizationUrl($callbackUrl));
        } catch (\Throwable $exception) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Failed to initiate OAuth flow.',
                    'error' => $exception->getMessage(),
                ], 422);
            }

            return redirect()->route('larapost.dashboard')->with('error', $exception->getMessage());
        }
    }

    public function callback(Request $request, string $platform): RedirectResponse|JsonResponse
    {
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

            return redirect()
                ->route('larapost.dashboard')
                ->with('success', sprintf('%s account "%s" connected successfully.', ucfirst($platform), $account->account_name));
        } catch (\Throwable $exception) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Account connection failed.',
                    'error' => $exception->getMessage(),
                ], 422);
            }

            return redirect()->route('larapost.dashboard')->with('error', $exception->getMessage());
        }
    }
}
