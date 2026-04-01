<?php

use App\Http\Middleware\RequestLogContext;
use App\Http\Middleware\ResolveAuth0BearerUser;
use App\Jobs\PostRudaPandaRoomIcebreakerJob;
use App\Models\Room;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        channels: null,
        health: '/up',
    )
    ->withBroadcasting(
        __DIR__.'/../routes/channels.php',
        ['middleware' => ['web', ResolveAuth0BearerUser::class]],
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->statefulApi();
        $middleware->append(RequestLogContext::class);

        $trusted = env('TRUSTED_PROXIES');
        if (is_string($trusted) && $trusted !== '') {
            $at = $trusted === '*' ? '*' : array_values(array_filter(array_map('trim', explode(',', $trusted))));
            $middleware->trustProxies(at: $at);
        }
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(function (Request $request, Throwable $e) {
            return $request->is('api/*');
        });
    })
    ->withSchedule(function (Illuminate\Console\Scheduling\Schedule $schedule): void {
        // T183: room idle icebreakers (handler will no-op when disabled in chat_settings).
        // This schedules per-room jobs by scanning rooms inside the job dispatcher task.
        $schedule->call(function (): void {
            $rooms = Room::query()
                ->where('access', 0)
                ->get(['room_id']);

            foreach ($rooms as $room) {
                PostRudaPandaRoomIcebreakerJob::dispatch(
                    roomId: (int) $room->room_id,
                    idempotencyKey: (string) Str::uuid(),
                );
            }
        })->everyFiveMinutes()->name('ruda-panda:icebreakers');
    })
    ->create();
