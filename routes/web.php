<?php

use Illuminate\Support\Facades\Route;
use SocialSync\Http\Controllers\OAuthController;

Route::prefix('social-sync')->name('social-sync.')->group(function () {

    // OAuth callback routes
    Route::get('callback/{platform}', [OAuthController::class, 'callback'])
        ->name('callback');

    // Connect account route
    Route::get('connect/{platform}', [OAuthController::class, 'connect'])
        ->name('connect');

});
