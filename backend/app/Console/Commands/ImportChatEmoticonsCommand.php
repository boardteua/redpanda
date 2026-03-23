<?php

namespace App\Console\Commands;

use App\Models\ChatEmoticon;
use App\Services\Chat\ChatEmoticonFileService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

/**
 * Імпорт файлів з public/emoticon у таблицю chat_emoticons (T63).
 */
class ImportChatEmoticonsCommand extends Command
{
    protected $signature = 'chat:import-emoticons {--dry-run : Лише показати дії без запису в БД}';

    protected $description = 'Створити записи chat_emoticons для файлів у public/emoticon (пропускає вже наявні коди)';

    public function handle(ChatEmoticonFileService $files): int
    {
        $dir = $files->publicEmoticonDirectory();
        if (! File::isDirectory($dir)) {
            $this->error('Тека не існує: '.$dir);

            return self::FAILURE;
        }

        $dry = (bool) $this->option('dry-run');
        $patterns = ['*.gif', '*.png', '*.webp'];
        $paths = [];
        foreach ($patterns as $pat) {
            foreach (File::glob($dir.DIRECTORY_SEPARATOR.$pat) ?: [] as $p) {
                $paths[] = $p;
            }
        }
        $paths = array_values(array_unique($paths));
        sort($paths);

        $created = 0;
        $skipped = 0;

        foreach ($paths as $path) {
            $base = basename($path);
            if (! $files->isSafeBasename($base)) {
                $this->warn('Пропуск (небезпечне ім’я): '.$base);
                $skipped++;

                continue;
            }

            $stem = pathinfo($base, PATHINFO_FILENAME);
            $code = Str::lower((string) preg_replace('/[^a-zA-Z0-9_]+/', '_', $stem));
            $code = trim((string) preg_replace('/_+/', '_', $code), '_');
            if ($code === '' || strlen($code) > 64) {
                $this->warn('Пропуск (код порожній або завдовгий): '.$base);
                $skipped++;

                continue;
            }

            if (ChatEmoticon::query()->where('code', $code)->exists()) {
                $skipped++;

                continue;
            }

            if ($dry) {
                $this->line("[dry-run] створити: {$code} ← {$base}");
                $created++;

                continue;
            }

            ChatEmoticon::query()->create([
                'code' => $code,
                'display_name' => $stem,
                'file_name' => $base,
                'sort_order' => 0,
                'is_active' => true,
                'keywords' => null,
            ]);
            $created++;
        }

        $this->info($dry ? 'Dry-run: '.($created).' нових.' : 'Імпортовано: '.$created.'. Пропущено: '.$skipped.'.');

        return self::SUCCESS;
    }
}
