<?php

namespace App\Console\Commands;

use App\Services\LegacyBoardImport\LegacyBoardImportService;
use Illuminate\Console\Command;
use Throwable;

class ChatLegacyImportStagingCommand extends Command
{
    protected $signature = 'chat:legacy-import-staging
                            {--dry-run : Лише оцінка обсягу без запису в цільову БД}
                            {--force : Дозволити на production (небезпечно)}';

    protected $description = 'Імпорт rooms/users/chat з legacy MySQL у порожню схему redpanda (staging, T13)';

    public function handle(LegacyBoardImportService $import): int
    {
        if (app()->environment('production') && ! $this->option('force')) {
            $this->error('На production потрібен прапорець --force (і окреме рішення оператора).');

            return self::FAILURE;
        }

        if (! $import->legacyDatabaseConfigured()) {
            $this->error('Не задано LEGACY_DB_DATABASE. Див. docs/chat-v2/T13-ETL-STAGING.md');

            return self::FAILURE;
        }

        try {
            $import->assertLegacyReachable();
        } catch (Throwable $e) {
            $this->error('Не вдалося підключитися до legacy: '.$e->getMessage());

            return self::FAILURE;
        }

        if ($this->option('dry-run')) {
            try {
                $report = $import->importStaging(true);
            } catch (Throwable $e) {
                $this->error($e->getMessage());

                return self::FAILURE;
            }

            $this->info('Dry-run (запису не було):');
            $this->line('  rooms: '.$report['rooms']);
            $this->line('  users (усього у legacy.users): '.$report['users_legacy_total']);
            $this->line('  users (буде імпортовано, є ≥1 рядок у chat): '.$report['users']);
            $this->line('  users пропущено (немає публічних постів у chat): '.$report['users_skipped_no_posts']);
            $this->line('  stub-користувачів (немає в legacy.users, але є в chat): '.$report['stubs']);
            $this->line('  рядків chat для імпорту: '.$report['chat_rows']);

            return self::SUCCESS;
        }

        if (! $this->confirm('Підтвердіть імпорт у поточну БД (має бути порожня users/chat/rooms). Продовжити?', false)) {
            $this->warn('Скасовано.');

            return self::FAILURE;
        }

        try {
            $report = $import->importStaging(false);
        } catch (Throwable $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        $this->info('Імпорт завершено:');
        $this->line('  rooms: '.$report['rooms']);
        $this->line('  users (імпортовано, з публічними постами): '.$report['users']);
        $this->line('  users (усього було у legacy.users): '.$report['users_legacy_total']);
        $this->line('  users пропущено (без публічних постів у chat): '.$report['users_skipped_no_posts']);
        $this->line('  stub-користувачів: '.$report['stubs']);
        $this->line('  chat (вставлено): '.$report['chat_rows']);
        $this->line('  chat (пропущено): '.$report['chat_skipped']);
        $this->comment('Поле file у chat обнулено; паролі лише для валідних bcrypt; без публічних постів у chat — user не імпортується (T113); див. T13-ETL-STAGING.md');

        return self::SUCCESS;
    }
}
