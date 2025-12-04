<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use SocialSync\Facades\SocialMedia;
use SocialSync\Models\SocialAccount;
use SocialSync\Models\ScheduledPost;

class SocialSyncTestController extends Controller
{
    /**
     * Display the test dashboard
     */
    public function index()
    {
        $accounts = SocialAccount::active()
            ->orderBy('platform')
            ->orderBy('account_name')
            ->get();

        $recentPosts = ScheduledPost::with('account')
            ->latest()
            ->take(10)
            ->get();

        return view('social-sync-test', compact('accounts', 'recentPosts'));
    }

    /**
     * Handle post submission
     */
    public function post(Request $request)
    {
        $validated = $request->validate([
            'content' => 'required|string|max:5000',
            'platforms' => 'required|array|min:1',
            'platforms.*' => 'in:facebook,instagram,twitter,linkedin',
            'image' => 'nullable|image|max:5120', // 5MB max
        ]);

        try {
            $imagePath = null;

            // Handle image upload
            if ($request->hasFile('image')) {
                $imagePath = $request->file('image')->store('social-posts', 'public');
                $imagePath = storage_path('app/public/' . $imagePath);
            }

            // Build the post
            $postBuilder = SocialMedia::post()
                ->content($validated['content'])
                ->platforms($validated['platforms']);

            // Add image if uploaded
            if ($imagePath) {
                $postBuilder->image($imagePath);
            }

            // Publish
            $results = $postBuilder->publish();

            // Count successes
            $successCount = collect($results)->where('success', true)->count();
            $totalCount = count($results);

            if ($successCount === $totalCount) {
                return back()->with([
                    'success' => "Posted successfully to all {$successCount} account(s)!",
                    'results' => $results
                ]);
            } elseif ($successCount > 0) {
                return back()->with([
                    'warning' => "Posted to {$successCount} of {$totalCount} account(s). Some failed.",
                    'results' => $results
                ]);
            } else {
                return back()->with([
                    'error' => 'All posts failed. Check the logs for details.',
                    'results' => $results
                ]);
            }

        } catch (\Exception $e) {
            return back()->with('error', 'Error: ' . $e->getMessage());
        }
    }

    /**
     * Schedule a post
     */
    public function schedule(Request $request)
    {
        $validated = $request->validate([
            'content' => 'required|string|max:5000',
            'platforms' => 'required|array|min:1',
            'scheduled_at' => 'required|date|after:now',
            'image' => 'nullable|image|max:5120',
        ]);

        try {
            $imagePath = null;

            if ($request->hasFile('image')) {
                $imagePath = $request->file('image')->store('social-posts', 'public');
                $imagePath = storage_path('app/public/' . $imagePath);
            }

            $postBuilder = SocialMedia::post()
                ->content($validated['content'])
                ->platforms($validated['platforms'])
                ->scheduleFor($validated['scheduled_at']);

            if ($imagePath) {
                $postBuilder->image($imagePath);
            }

            $scheduledPosts = $postBuilder->publish();

            return back()->with([
                'success' => 'Post scheduled successfully for ' . count($scheduledPosts) . ' account(s)!'
            ]);

        } catch (\Exception $e) {
            return back()->with('error', 'Error: ' . $e->getMessage());
        }
    }

    /**
     * View scheduled posts
     */
    public function scheduled()
    {
        $scheduled = ScheduledPost::with('account')
            ->pending()
            ->orderBy('scheduled_for')
            ->get();

        return view('social-sync-scheduled', compact('scheduled'));
    }

    /**
     * Cancel a scheduled post
     */
    public function cancelScheduled($id)
    {
        $post = ScheduledPost::findOrFail($id);

        if ($post->status === 'pending') {
            $post->update(['status' => 'cancelled']);
            return back()->with('success', 'Scheduled post cancelled.');
        }

        return back()->with('error', 'Cannot cancel this post.');
    }
}
