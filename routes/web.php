<?php

use Illuminate\Support\Facades\Route;
use SocialSync\Http\Controllers\OAuthController;

Route::middleware(config('larapost.routes.middleware', ['web']))
    ->prefix(config('larapost.routes.prefix', 'larapost'))
    ->name('larapost.')
    ->group(function (): void {
        Route::get('connect/{platform}', [OAuthController::class, 'connect'])->name('connect');
        Route::get('callback/{platform}', [OAuthController::class, 'callback'])->name('callback');
    });
