<?php

namespace App\Console\Commands;

use App\Services\LegacyBoardImport\LegacyBoardImportService;
use Illuminate\Console\Command;
use Throwable;

/**
 * Prod-обгортка над тим самим пайплайном, що й chat:legacy-import-staging (T130 / T128).
 * Політика: цільові таблиці users / rooms / chat мають бути **порожніми** — див. docs/chat-v2/T130-LEGACY-PUBLIC-CHAT-IMPORT.md.
 */
class ChatLegacyImportProductionCommand extends Command
{
    protected $signature = 'chat:legacy-import-production
                            {--dry-run : Лише оцінка обсягу без запису в цільову БД}
                            {--force : Дозволити на production (небезпечно)}';

    protected $description = 'Імпорт rooms/users/chat з legacy у порожню схему redpanda (production, T130; runbook T128)';

    public function handle(LegacyBoardImportService $import): int
    {
        if (app()->environment('production') && ! $this->option('force')) {
            $this->error('На production потрібен прапорець --force (і окреме рішення оператора). Див. docs/chat-v2/T128-LEGACY-PROD-IMPORT-RUNBOOK.md');

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
            $this->line('  chat пропущено (немає user_id у legacy.users): '.$report['chat_skipped_no_legacy_user']);
            $this->line('  рядків chat з валідним автором у legacy.users (верхня межа; факт менший через T113): '.$report['chat_rows']);

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
        $this->line('  chat без legacy.users (не імпортуються): '.$report['chat_skipped_no_legacy_user']);
        $this->line('  chat (вставлено): '.$report['chat_rows']);
        $this->line('  chat (пропущено загалом): '.$report['chat_skipped']);
        $this->comment('client_message_id стабільний від legacy post_id; file=0; chat без legacy.users не імпортуються; T113/T129/T130 — див. docs/chat-v2/T130-LEGACY-PUBLIC-CHAT-IMPORT.md');

        return self::SUCCESS;
    }
}
