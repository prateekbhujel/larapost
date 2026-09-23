<?php

namespace SocialSync\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;
use SocialSync\Models\SocialAccount;
use SocialSync\SocialMediaManager;
use Throwable;

class DoctorCommand extends Command
{
    protected $signature = 'larapost:doctor';

    protected $description = 'Check LaraPost database, publishing, queue, scheduler, dashboard, TikTok, and MCP configuration.';

    public function handle(SocialMediaManager $manager): int
    {
        $rows = [];
        $failed = false;

        foreach (['social_accounts', 'scheduled_posts', 'larapost_platform_credentials'] as $table) {
            $ok = $this->hasTable($table);
            $rows[] = ['Database', $table, $ok ? 'OK' : 'MISSING'];
            $failed = $failed || !$ok;
        }

        $platforms = $manager->supportedPlatforms();
        $rows[] = ['Platforms', implode(', ', $platforms), $platforms === [] ? 'MISSING' : 'OK'];
        $failed = $failed || $platforms === [];

        $activeAccounts = $this->hasTable('social_accounts')
            ? SocialAccount::query()->active()->count()
            : 0;
        $rows[] = ['Accounts', 'active connected accounts', (string) $activeAccounts];

        $queueEnabled = (bool) config('larapost.queue.enabled', false);
        $rows[] = [
            'Queue',
            $queueEnabled
                ? ((string) config('larapost.queue.connection', 'default') . ' / ' . (string) config('larapost.queue.queue_name', 'larapost'))
                : 'synchronous scheduler mode',
            $queueEnabled ? 'ENABLED' : 'OPTIONAL',
        ];

        $rows[] = [
            'Scheduler',
            'automatic larapost:run-scheduled registration',
            config('larapost.scheduler.enabled', true) ? 'ENABLED' : 'DISABLED',
        ];

        if (in_array('tiktok', $platforms, true)) {
            $mode = strtolower(trim((string) config('larapost.platforms.tiktok.publish_mode', 'upload')));
            $validMode = in_array($mode, ['upload', 'direct'], true);

            $rows[] = [
                'TikTok',
                'publish mode',
                $validMode ? strtoupper($mode) : 'INVALID',
            ];

            if (!$validMode) {
                $failed = true;
            } elseif ($mode === 'direct') {
                $this->warn(
                    'TikTok Direct Post requires a host application to render current creator info, collect privacy and interaction choices, and obtain explicit creator consent. LaraPost MCP intentionally does not perform TikTok Direct Post.'
                );
            }
        }

        $mcpEnabled = (bool) config('larapost.mcp.enabled', false);
        $mcpToken = (string) config('larapost.mcp.token', '');

        if ($mcpEnabled && !class_exists(\Laravel\Mcp\Server::class)) {
            $rows[] = ['MCP', 'laravel/mcp runtime', 'PACKAGE MISSING'];
            $failed = true;
        } elseif ($mcpEnabled && $mcpToken === '') {
            $rows[] = ['MCP', 'remote AI server', 'TOKEN MISSING'];
            $failed = true;
        } else {
            $rows[] = [
                'MCP',
                $mcpEnabled ? (string) config('larapost.mcp.path', '/larapost/mcp') : 'remote AI server',
                $mcpEnabled ? 'ENABLED' : 'DISABLED',
            ];
        }

        $operatorMiddleware = (array) config('larapost.routes.operator_middleware', []);
        $operatorProtected = $operatorMiddleware !== []
            && (in_array('auth', $operatorMiddleware, true)
                || collect($operatorMiddleware)->contains(
                    static fn ($middleware): bool => str_contains(strtolower((string) $middleware), 'auth')
                ));

        $rows[] = [
            'Dashboard',
            implode(', ', $operatorMiddleware),
            $operatorProtected ? 'PROTECTED' : 'REVIEW ACCESS',
        ];

        $this->table(['Area', 'Detail', 'Status'], $rows);

        if (!$operatorProtected) {
            $this->warn(
                'The operator dashboard can publish posts and change credentials. Confirm your custom middleware restricts access.'
            );
        }

        if ($failed) {
            $this->error('LaraPost found configuration that needs attention.');

            return self::FAILURE;
        }

        $this->info('LaraPost looks ready.');

        return self::SUCCESS;
    }

    protected function hasTable(string $table): bool
    {
        try {
            return Schema::hasTable($table);
        } catch (Throwable) {
            return false;
        }
    }
}
