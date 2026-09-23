<?php

namespace SocialSync\Console\Commands;

use Illuminate\Console\Command;
use SocialSync\Facades\SocialMedia;

class AddAccountCommand extends Command
{
    protected $signature = 'larapost:add-account {platform : facebook|twitter|linkedin|tiktok}';

    protected $description = 'Show the authenticated browser URL used to connect a social account.';

    public function handle(): int
    {
        $platform = strtolower((string) $this->argument('platform'));
        $supported = SocialMedia::supportedPlatforms();

        if (!in_array($platform, $supported, true)) {
            $this->error('Invalid or disabled platform. Enabled: ' . implode(', ', $supported));

            return self::FAILURE;
        }

        $this->newLine();
        $this->info('Connect accounts through LaraPost OAuth in your authenticated browser session.');
        $this->line('Open: ' . route('larapost.connect', ['platform' => $platform]));
        $this->newLine();
        $this->line('The callback is handled by your Laravel app so OAuth state can be verified before credentials are stored.');

        return self::SUCCESS;
    }
}
