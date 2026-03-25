<?php

namespace App\Console\Commands;

use App\Support\LegacyMediaPathSync;
use Illuminate\Console\Command;
use Symfony\Component\Process\Process;

/**
 * T113: обгортка над rsync для копіювання файлів аватарок з legacy (ім’я файлу = user_name).
 */
class ChatLegacySyncAvatarsCommand extends Command
{
    protected $signature = 'chat:legacy-sync-avatars
                            {--dry-run : Передати rsync -n (лише перелік, без запису)}';

    protected $description = 'Копіювання аватарок legacy→redpanda (rsync; локальні шляхи або user@host:…; T113)';

    public function handle(): int
    {
        $source = (string) config('legacy.avatar_rsync_source', '');
        $dest = (string) config('legacy.avatar_rsync_dest', '');

        if ($source === '' || $dest === '') {
            $this->error('Задайте LEGACY_AVATAR_RSYNC_SOURCE та LEGACY_AVATAR_RSYNC_DEST у .env. Див. docs/chat-v2/T113-LEGACY-AVATARS.md');

            return self::FAILURE;
        }

        $dry = (bool) $this->option('dry-run');
        $args = LegacyMediaPathSync::rsyncArgv($source, $dest, $dry);
        $mode = LegacyMediaPathSync::sourceUsesSshTransport($source) ? 'rsync+ssh' : 'rsync (локально на ФС)';

        $this->info('Запуск: '.$mode.' '.($dry ? '(dry-run) ' : '').'…');

        $process = new Process($args);
        $process->setTimeout(3600);
        $process->run(function (string $type, string $buffer): void {
            $this->output->write($buffer);
        });

        if (! $process->isSuccessful()) {
            $this->error(trim($process->getErrorOutput() ?: $process->getOutput()));

            return self::FAILURE;
        }

        $this->info('Готово.');

        return self::SUCCESS;
    }
}
