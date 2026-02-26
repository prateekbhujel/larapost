<?php

namespace SocialSync\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\View\View;
use SocialSync\Facades\SocialMedia;
use SocialSync\Models\ScheduledPost;
use SocialSync\Models\SocialAccount;

class SocialSyncTestController extends Controller
{
    public function index(): View
    {
        $accounts = SocialAccount::query()->active()->orderBy('platform')->orderBy('account_name')->get();
        $recentPosts = ScheduledPost::query()->with('account')->latest()->limit(10)->get();

        return view('social-sync::test-dashboard', [
            'accounts' => $accounts,
            'recentPosts' => $recentPosts,
        ]);
    }

    public function post(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'content' => ['required', 'string', 'max:5000'],
            'platforms' => ['required', 'array', 'min:1'],
            'platforms.*' => ['in:facebook,instagram,twitter,linkedin'],
        ]);

        try {
            $results = SocialMedia::post()
                ->content($validated['content'])
                ->platforms($validated['platforms'])
                ->publish();

            $successCount = collect($results)->where('success', true)->count();

            return back()->with('success', sprintf('Published to %d account(s).', $successCount));
        } catch (\Throwable $exception) {
            return back()->with('error', $exception->getMessage());
        }
    }
}
