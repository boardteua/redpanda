<?php

namespace App\Providers;

use App\Models\ChatMessage;
use App\Models\Image;
use App\Models\Room;
use App\Models\User;
use App\Policies\ChatMessagePolicy;
use App\Policies\ImagePolicy;
use App\Policies\RoomPolicy;
use App\Policies\UserPolicy;
use App\Support\ChatThrottleRules;
use Illuminate\Auth\Events\Authenticated;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Event::listen(Authenticated::class, function (Authenticated $event): void {
            Log::shareContext(['user_id' => $event->user->getAuthIdentifier()]);
        });

        Gate::define('moderate', fn (User $user): bool => $user->canModerate());

        Gate::define('chat-admin', fn (User $user): bool => $user->isChatAdmin());

        Gate::policy(User::class, UserPolicy::class);
        Gate::policy(Room::class, RoomPolicy::class);
        Gate::policy(Image::class, ImagePolicy::class);
        Gate::policy(ChatMessage::class, ChatMessagePolicy::class);

        RateLimiter::for('auth-register', function (Request $request) {
            return Limit::perMinute(5)->by($request->ip());
        });

        RateLimiter::for('auth-login', function (Request $request) {
            return Limit::perMinute(5)->by($request->ip());
        });

        RateLimiter::for('auth-guest', function (Request $request) {
            return Limit::perMinute(20)->by($request->ip());
        });

        RateLimiter::for('chat-read', function (Request $request) {
            $user = $request->user();

            return Limit::perMinute(120)->by($user ? 'u:'.$user->id : 'ip:'.$request->ip());
        });

        RateLimiter::for('chat-mark-read', function (Request $request) {
            /** @var User|null $user */
            $user = $request->user();

            return Limit::perMinute(90)->by($user ? 'u:'.$user->id : 'ip:'.$request->ip());
        });

        RateLimiter::for('archive-read', function (Request $request) {
            $user = $request->user();

            return Limit::perMinute(60)->by($user ? 'u:'.$user->id : 'ip:'.$request->ip());
        });

        RateLimiter::for('image-upload', function (Request $request) {
            /** @var User $user маршрут під auth:sanctum */
            $user = $request->user();

            return Limit::perMinute(ChatThrottleRules::imageUploadsPerMinute($user))
                ->by('u:'.$user->id);
        });

        RateLimiter::for('image-read', function (Request $request) {
            $user = $request->user();

            return Limit::perMinute(180)->by($user ? 'u:'.$user->id : 'ip:'.$request->ip());
        });

        RateLimiter::for('chat-post', function (Request $request) {
            /** @var User $user маршрут під auth:sanctum */
            $user = $request->user();

            return Limit::perMinute(ChatThrottleRules::postsPerMinute($user))
                ->by('u:'.$user->id);
        });

        RateLimiter::for('room-create', function (Request $request) {
            /** @var User $user маршрут під auth:sanctum */
            $user = $request->user();

            return Limit::perMinute(10)->by('u:'.$user->id);
        });

        RateLimiter::for('private-read', function (Request $request) {
            $user = $request->user();

            return Limit::perMinute(120)->by($user ? 'u:'.$user->id : 'ip:'.$request->ip());
        });

        RateLimiter::for('private-post', function (Request $request) {
            $user = $request->user();

            return Limit::perMinute(30)->by($user ? 'u:'.$user->id : 'ip:'.$request->ip());
        });

        RateLimiter::for('mod-actions', function (Request $request) {
            $user = $request->user();

            return Limit::perMinute(120)->by($user ? 'u:'.$user->id : 'ip:'.$request->ip());
        });

        RateLimiter::for('me-profile', function (Request $request) {
            $user = $request->user();

            return Limit::perMinute(60)->by($user ? 'u:'.$user->id : 'ip:'.$request->ip());
        });

        RateLimiter::for('me-account', function (Request $request) {
            $user = $request->user();

            return Limit::perMinute(10)->by($user ? 'u:'.$user->id : 'ip:'.$request->ip());
        });
    }
}
