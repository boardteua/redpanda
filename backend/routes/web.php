<?php

use App\Http\Controllers\HealthController;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\Facades\Route;
use Illuminate\View\Middleware\ShareErrorsFromSession;

Route::get('/health/ready', [HealthController::class, 'ready'])
    ->withoutMiddleware([
        StartSession::class,
        AddQueuedCookiesToResponse::class,
        ShareErrorsFromSession::class,
        PreventRequestForgery::class,
    ])
    ->name('health.ready');

Route::view('/', 'spa');

if (app()->environment('local')) {
    Route::view('/__qa/chat-api', 'qa.chat-api');
}

Route::fallback(function () {
    return view('spa');
});
