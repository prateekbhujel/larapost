<?php

use Illuminate\Support\Facades\Route;
use SocialSync\Http\Controllers\DashboardController;
use SocialSync\Http\Controllers\OAuthController;

Route::middleware(config('larapost.routes.middleware', ['web']))
    ->prefix(config('larapost.routes.prefix', 'larapost'))
    ->name('larapost.')
    ->group(function (): void {
        if (config('larapost.ui.enabled', true)) {
            Route::get('dashboard', [DashboardController::class, 'index'])->name('dashboard');
            Route::post('publish', [DashboardController::class, 'publish'])->name('publish');
            Route::post('publish-bulk', [DashboardController::class, 'publishBulk'])->name('publish.bulk');
            Route::post('accounts/{account}/toggle', [DashboardController::class, 'toggleAccount'])->name('accounts.toggle');
            Route::post('settings/{platform}', [DashboardController::class, 'storeCredentials'])->name('settings.store');
            Route::delete('settings/{platform}', [DashboardController::class, 'destroyCredentials'])->name('settings.destroy');
        }

        Route::get('connect/{platform}', [OAuthController::class, 'connect'])->name('connect');
        Route::get('callback/{platform}', [OAuthController::class, 'callback'])->name('callback');
    });
