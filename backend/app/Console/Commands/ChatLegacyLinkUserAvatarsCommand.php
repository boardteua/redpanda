<?php

namespace App\Console\Commands;

use App\Services\LegacyBoardImport\LegacyUserAvatarLinkService;
use Illuminate\Console\Command;

class ChatLegacyLinkUserAvatarsCommand extends Command
{
    protected $signature = 'chat:legacy-link-user-avatars
                            {--dry-run : Лише підрахунок без запису в БД і без копіювання у chat-images}
                            {--force : Дозволити на production}
                            {--chunk=200 : Розмір chunk користувачів}
                            {--dir= : Каталог з rsync-аватарками (за замовчуванням legacy.avatar_rsync_dest)}';

    protected $description = 'Прив’язати файли з каталогу legacy-аватарок до users.avatar_image_id (images на диску chat_images; T113)';

    public function handle(LegacyUserAvatarLinkService $service): int
    {
        if (app()->environment('production') && ! $this->option('force')) {
            $this->error('На production потрібен --force (бекап БД).');

            return self::FAILURE;
        }

        $dir = trim((string) $this->option('dir'));
        if ($dir === '') {
            $dir = (string) config('legacy.avatar_rsync_dest', '');
        }
        if ($dir === '') {
            $this->error('Задайте каталог: --dir=… або LEGACY_AVATAR_RSYNC_DEST у .env (після chat:legacy-sync-avatars). Див. docs/chat-v2/T113-LEGACY-AVATARS.md');

            return self::FAILURE;
        }

        $chunk = max(1, min(2000, (int) $this->option('chunk')));
        $dry = (bool) $this->option('dry-run');

        try {
            $r = $service->run($dir, $dry, $chunk);
        } catch (\InvalidArgumentException $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        $this->info($dry ? 'Dry-run:' : 'Готово:');
        $this->line('  прив’язано (або б з прив’язано): '.$r['linked']);
        $this->line('  немає файлу за user_name: '.$r['skipped_no_file']);
        $this->line('  файл не є валідним зображенням / завеликий: '.$r['skipped_invalid_image']);
        $this->line('  помилка запису: '.$r['skipped_error']);

        return self::SUCCESS;
    }
}
