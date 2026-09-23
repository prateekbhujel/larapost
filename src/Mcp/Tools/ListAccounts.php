<?php

namespace SocialSync\Mcp\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;
use SocialSync\Models\SocialAccount;

#[Description('List connected LaraPost social accounts. Credentials and tokens are never returned.')]
#[IsReadOnly]
class ListAccounts extends Tool
{
    public function handle(Request $request): Response
    {
        $platform = strtolower(trim((string) $request->get('platform', '')));
        $activeOnly = filter_var($request->get('active_only', true), FILTER_VALIDATE_BOOL);

        $query = SocialAccount::query()->orderBy('platform')->orderBy('account_name');

        if ($platform !== '') {
            $query->where('platform', $platform);
        }

        if ($activeOnly) {
            $query->active();
        }

        $accounts = $query->get()->map(static fn (SocialAccount $account): array => [
            'id' => $account->id,
            'platform' => $account->platform,
            'name' => $account->account_name,
            'username' => $account->account_username,
            'platform_id' => $account->account_id_on_platform,
            'active' => (bool) $account->is_active,
            'last_used_at' => $account->last_used_at?->toIso8601String(),
        ])->values()->all();

        return Response::json(['accounts' => $accounts]);
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'platform' => $schema->string()->max(40)->description('Optional platform filter such as facebook, twitter, linkedin, or tiktok.'),
            'active_only' => $schema->boolean()->description('When true, only return active accounts. Defaults to true.'),
        ];
    }
}
