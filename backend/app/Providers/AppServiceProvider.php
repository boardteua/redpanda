<?php

namespace App\Providers;

use App\Chat\SlashCommands\Handlers\AwaySlashCommandHandler;
use App\Chat\SlashCommands\Handlers\BanSlashCommandHandler;
use App\Chat\SlashCommands\Handlers\ChatUploadGatingSlashCommandHandler;
use App\Chat\SlashCommands\Handlers\ClearRoomJournalSlashCommandHandler;
use App\Chat\SlashCommands\Handlers\FriendSlashCommandHandler;
use App\Chat\SlashCommands\Handlers\GlobalAnnouncementSlashCommandHandler;
use App\Chat\SlashCommands\Handlers\GsoundSlashCommandHandler;
use App\Chat\SlashCommands\Handlers\IgnoreClearSlashCommandHandler;
use App\Chat\SlashCommands\Handlers\IgnoreSlashCommandHandler;
use App\Chat\SlashCommands\Handlers\InvisibleVisibilitySlashCommandHandler;
use App\Chat\SlashCommands\Handlers\KickSlashCommandHandler;
use App\Chat\SlashCommands\Handlers\ManualSlashCommandHandler;
use App\Chat\SlashCommands\Handlers\MeSlashCommandHandler;
use App\Chat\SlashCommands\Handlers\MsgSlashCommandHandler;
use App\Chat\SlashCommands\Handlers\MuteSlashCommandHandler;
use App\Chat\SlashCommands\Handlers\SeenSlashCommandHandler;
use App\Chat\SlashCommands\Handlers\SilentModeSlashCommandHandler;
use App\Chat\SlashCommands\Handlers\StaffRoleSlashCommandHandler;
use App\Chat\SlashCommands\Handlers\TopicSlashCommandHandler;
use App\Chat\SlashCommands\Handlers\UnmuteSlashCommandHandler;
use App\Chat\SlashCommands\SlashCommandRegistry;
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
use Illuminate\Auth\Notifications\ResetPassword as ResetPasswordNotification;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Foundation\Console\ServeCommand;
use Illuminate\Http\Request;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        if (! in_array('PHP_INI_SCAN_DIR', ServeCommand::$passthroughVariables, true)) {
            ServeCommand::$passthroughVariables[] = 'PHP_INI_SCAN_DIR';
        }

        $this->app->singleton(SlashCommandRegistry::class, function ($app) {
            $registry = new SlashCommandRegistry;
            $registry->register('manual', $app->make(ManualSlashCommandHandler::class));
            $registry->register('away', $app->make(AwaySlashCommandHandler::class));
            $registry->register('me', $app->make(MeSlashCommandHandler::class));
            $registry->register('seen', $app->make(SeenSlashCommandHandler::class));
            $registry->register('msg', $app->make(MsgSlashCommandHandler::class));
            $registry->register('friend', $app->make(FriendSlashCommandHandler::class));
            $registry->register('ignore', $app->make(IgnoreSlashCommandHandler::class));
            $registry->register('ignoreclear', $app->make(IgnoreClearSlashCommandHandler::class));
            $registry->register('mute', $app->make(MuteSlashCommandHandler::class));
            $registry->register('kick', $app->make(KickSlashCommandHandler::class));
            $registry->register('unmute', $app->make(UnmuteSlashCommandHandler::class));
            $registry->register('upon', new ChatUploadGatingSlashCommandHandler(true));
            $registry->register('upoff', new ChatUploadGatingSlashCommandHandler(false));
            $registry->register('ban', $app->make(BanSlashCommandHandler::class));
            $registry->register('topic', $app->make(TopicSlashCommandHandler::class));
            $registry->register('clear', $app->make(ClearRoomJournalSlashCommandHandler::class));
            $registry->register('setuser', new StaffRoleSlashCommandHandler('setuser'));
            $registry->register('setmod', new StaffRoleSlashCommandHandler('setmod'));
            $registry->register('setvip', new StaffRoleSlashCommandHandler('setvip'));
            $registry->register('setadmin', new StaffRoleSlashCommandHandler('setadmin'));
            $invis = $app->make(InvisibleVisibilitySlashCommandHandler::class);
            $registry->register('invisible', $invis);
            $registry->register('visible', $invis);
            $registry->register('silent', $app->make(SilentModeSlashCommandHandler::class));
            $registry->register('gsound', $app->make(GsoundSlashCommandHandler::class));
            $registry->register('global', $app->make(GlobalAnnouncementSlashCommandHandler::class));

            return $registry;
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // За TLS-термінатором (nginx → Docker) без цього @vite() / url() дають http:// → mixed content на https://.
        if ($this->app->environment('production')) {
            URL::forceScheme('https');
        }

        ResetPasswordNotification::toMailUsing(function (object $notifiable, #[\SensitiveParameter] string $token): MailMessage {
            $email = urlencode((string) $notifiable->getEmailForPasswordReset());
            $url = rtrim((string) config('app.url'), '/').'/reset-password?token='.urlencode($token).'&email='.$email;
            $expire = (int) config('auth.passwords.'.config('auth.defaults.passwords').'.expire');

            return (new MailMessage)
                ->subject('Скидання пароля')
                ->line('Ви отримали цей лист, бо для вашого облікового запису запитали скидання пароля.')
                ->action('Скинути пароль', $url)
                ->line("Посилання діє {$expire} хвилин.")
                ->line('Якщо ви цього не робили — проігноруйте лист; пароль не зміниться.');
        });

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
            $ip = $request->ip();
            $name = Str::lower(trim((string) $request->input('user_name', '')));
            $limits = [
                Limit::perMinute(5)->by('auth-login-ip:'.$ip),
            ];
            if ($name !== '') {
                // Обмеження спроб на цільовий нік (усі IP) — стримує розподілений перебір одного акаунта.
                $limits[] = Limit::perMinute(30)->by('auth-login-name:'.hash('sha256', $name));
            }

            return $limits;
        });

        RateLimiter::for('auth-guest', function (Request $request) {
            return Limit::perMinute(12)->by($request->ip());
        });

        RateLimiter::for('auth-forgot-password', function (Request $request) {
            return Limit::perMinute(5)->by('auth-forgot-password:'.$request->ip());
        });

        RateLimiter::for('auth-reset-password', function (Request $request) {
            return Limit::perMinute(10)->by('auth-reset-password:'.$request->ip());
        });

        RateLimiter::for('landing-read', function (Request $request) {
            return Limit::perMinute(120)->by($request->ip());
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

        RateLimiter::for('presence-status', function (Request $request) {
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

        RateLimiter::for('room-manage', function (Request $request) {
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

        RateLimiter::for('mod-user-read', function (Request $request) {
            $user = $request->user();

            return Limit::perMinute(90)->by($user ? 'u:'.$user->id : 'ip:'.$request->ip());
        });

        RateLimiter::for('mod-flagged-read', function (Request $request) {
            $user = $request->user();

            return Limit::perMinute(90)->by($user ? 'u:'.$user->id : 'ip:'.$request->ip());
        });

        RateLimiter::for('me-profile', function (Request $request) {
            $user = $request->user();

            return Limit::perMinute(60)->by($user ? 'u:'.$user->id : 'ip:'.$request->ip());
        });

        RateLimiter::for('me-account', function (Request $request) {
            $user = $request->user();

            return Limit::perMinute(10)->by($user ? 'u:'.$user->id : 'ip:'.$request->ip());
        });

        RateLimiter::for('emoticon-read', function (Request $request) {
            $user = $request->user();

            return Limit::perMinute(120)->by($user ? 'u:'.$user->id : 'ip:'.$request->ip());
        });

        RateLimiter::for('oembed-read', function (Request $request) {
            $user = $request->user();

            return Limit::perMinute(30)->by($user ? 'u:'.$user->id : 'ip:'.$request->ip());
        });

        RateLimiter::for('user-autocomplete', function (Request $request) {
            $user = $request->user();

            return Limit::perMinute(45)->by($user ? 'u:'.$user->id : 'ip:'.$request->ip());
        });
    }
}
