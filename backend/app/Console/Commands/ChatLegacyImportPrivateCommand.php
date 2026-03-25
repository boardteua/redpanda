<?php

namespace App\Console\Commands;

use App\Services\LegacyBoardImport\LegacyBoardImportService;
use App\Services\LegacyBoardImport\LegacyPrivateMessageImportService;
use Illuminate\Console\Command;
use Throwable;

class ChatLegacyImportPrivateCommand extends Command
{
    protected $signature = 'chat:legacy-import-private
                            {--dry-run : Лише підрахунок рядків без запису}
                            {--force : Дозволити на production (небезпечно)}';

    protected $description = 'Імпорт legacy private → private_messages (T131; після користувачів T129/T130)';

    public function handle(
        LegacyBoardImportService $legacyBoard,
        LegacyPrivateMessageImportService $import,
    ): int {
        if (app()->environment('production') && ! $this->option('force')) {
            $this->error('На production потрібен прапорець --force (і окреме рішення оператора). Див. docs/chat-v2/T128-LEGACY-PROD-IMPORT-RUNBOOK.md');

            return self::FAILURE;
        }

        if (! $legacyBoard->legacyDatabaseConfigured()) {
            $this->error('Не задано LEGACY_DB_DATABASE. Див. docs/chat-v2/T13-ETL-STAGING.md');

            return self::FAILURE;
        }

        try {
            $legacyBoard->assertLegacyReachable();
        } catch (Throwable $e) {
            $this->error('Не вдалося підключитися до legacy: '.$e->getMessage());

            return self::FAILURE;
        }

        if (! $import->legacyPrivateTableExists()) {
            $this->error('Таблиця legacy.private недоступна або відсутня.');

            return self::FAILURE;
        }

        if ($this->option('dry-run')) {
            try {
                $report = $import->import(true);
            } catch (Throwable $e) {
                $this->error($e->getMessage());

                return self::FAILURE;
            }
            $this->info('Dry-run private:');
            $this->line('  рядків у legacy.private: '.$report['legacy_rows']);

            return self::SUCCESS;
        }

        if (! $this->confirm('Підтвердіть імпорт у порожню private_messages. Продовжити?', false)) {
            $this->warn('Скасовано.');

            return self::FAILURE;
        }

        try {
            $report = $import->import(false);
        } catch (Throwable $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        $this->info('Імпорт приватів завершено:');
        $this->line('  вставлено: '.$report['inserted']);
        $this->line('  без hunter (нік → user): '.$report['skipped_no_hunter']);
        $this->line('  без target (нік → user): '.$report['skipped_no_target']);
        $this->line('  self / однакові учасники: '.$report['skipped_self']);
        $this->line('  порожнє тіло: '.$report['skipped_empty_body']);
        $this->line('  time/id некоректні: '.$report['skipped_bad_time']);
        $this->comment('Див. docs/chat-v2/T131-LEGACY-PRIVATE-IMPORT.md');

        return self::SUCCESS;
    }
}
