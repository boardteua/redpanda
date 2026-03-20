<?php

namespace App\Providers;

use App\Models\Room;
use App\Models\User;
use App\Policies\RoomPolicy;
use App\Policies\UserPolicy;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
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
        Gate::policy(User::class, UserPolicy::class);
        Gate::policy(Room::class, RoomPolicy::class);

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

        RateLimiter::for('chat-post', function (Request $request) {
            $user = $request->user();

            return Limit::perMinute(30)->by($user ? 'u:'.$user->id : 'ip:'.$request->ip());
        });
    }
}
