<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Symfony\Component\Process\Process;

/**
 * T132: rsync каталогу uploads з legacy (поряд з T113 для avatar).
 */
class ChatLegacySyncUploadsCommand extends Command
{
    protected $signature = 'chat:legacy-sync-uploads
                            {--dry-run : Передати rsync -n (лише перелік, без запису)}';

    protected $description = 'rsync uploads з legacy (LEGACY_UPLOADS_RSYNC_* у .env; T132)';

    public function handle(): int
    {
        $source = (string) config('legacy.uploads_rsync_source', '');
        $dest = (string) config('legacy.uploads_rsync_dest', '');

        if ($source === '' || $dest === '') {
            $this->error('Задайте LEGACY_UPLOADS_RSYNC_SOURCE та LEGACY_UPLOADS_RSYNC_DEST у .env. Див. docs/chat-v2/T132-LEGACY-MEDIA-MIGRATION.md');

            return self::FAILURE;
        }

        $args = [
            'rsync',
            '-a',
            '-v',
            '--human-readable',
            '-e',
            'ssh -o BatchMode=yes -o StrictHostKeyChecking=accept-new',
        ];

        if ($this->option('dry-run')) {
            $args[] = '-n';
        }

        $args[] = $source;
        $args[] = $dest;

        $this->info('Запуск: rsync '.($this->option('dry-run') ? '(dry-run) ' : '').'…');

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
