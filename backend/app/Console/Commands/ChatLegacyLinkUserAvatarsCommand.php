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

        $dirOption = $this->option('dir');
        $dirFromCli = is_string($dirOption) ? trim($dirOption) : '';
        $explicitDirArg = $dirFromCli !== '';

        $dir = $dirFromCli;
        if ($dir === '') {
            $dir = trim((string) config('legacy.avatar_rsync_dest', ''));
        }

        $canonical = storage_path('app/legacy-avatars');
        if ($dir === '') {
            $dir = $canonical;
        } elseif (! is_dir($dir) && ! $explicitDirArg) {
            // На хості rsync часто пише в /var/www/redpanda/backend/storage/...; у контейнері PHP проєкт — /var/www/html, той самий шлях хоста не існує.
            // Явний --dir не підміняємо (тести / оператор мають отримати помилку).
            if (is_dir($canonical)) {
                $this->warn('Каталог з env недоступний у ФС PHP: '.$dir);
                $this->line('  → використовується '.$canonical.' (змонтований backend у Docker: /var/www/html/storage/app/legacy-avatars).');
                $dir = $canonical;
            }
        }

        if (! is_dir($dir)) {
            $this->error(
                'Каталог legacy-аватарок не знайдено: '.($dir !== '' ? $dir : $canonical).'. '.
                'Після rsync має існувати storage/app/legacy-avatars у backend. У Docker задайте в env порожній LEGACY_AVATAR_RSYNC_DEST або шлях всередині контейнера (/var/www/html/storage/app/legacy-avatars). Див. T113.'
            );

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
