<?php

use Illuminate\Support\Facades\Route;
use SocialSync\Http\Controllers\OAuthController;

Route::middleware(config('social-sync.routes.middleware', ['web']))
    ->prefix(config('social-sync.routes.prefix', 'social-sync'))
    ->name('social-sync.')
    ->group(function (): void {
        Route::get('connect/{platform}', [OAuthController::class, 'connect'])->name('connect');
        Route::get('callback/{platform}', [OAuthController::class, 'callback'])->name('callback');
    });
