<?php

use App\Http\Controllers\HealthController;
use App\Http\Controllers\LlmsTxtController;
use App\Http\Controllers\PublicDocumentationController;
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

Route::get('/llms.txt', LlmsTxtController::class)->name('llms.txt');
Route::get('/docs/openapi.yaml', [PublicDocumentationController::class, 'openapiYaml'])->name('docs.openapi');
Route::get('/docs/chat-v2/AI-AGENT-FRIENDLY.md', [PublicDocumentationController::class, 'aiAgentFriendly'])
    ->name('docs.ai-agent-friendly');
Route::get('/docs/project-specs/chat-v2-setup.md', [PublicDocumentationController::class, 'chatV2Setup'])
    ->name('docs.chat-v2-setup');

Route::view('/', 'spa');

if (app()->environment('local')) {
    Route::view('/__qa/chat-api', 'qa.chat-api');
}

Route::fallback(function () {
    return view('spa');
});
