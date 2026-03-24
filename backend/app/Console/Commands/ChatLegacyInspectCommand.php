<?php

namespace App\Console\Commands;

use App\Services\LegacyBoardImport\LegacyBoardImportService;
use Illuminate\Console\Command;
use Throwable;

class ChatLegacyInspectCommand extends Command
{
    protected $signature = 'chat:legacy-inspect';

    protected $description = 'Підрахунок рядків у legacy БД (org100h) та перевірка сиріт chat→user / chat→room (T13)';

    public function handle(LegacyBoardImportService $import): int
    {
        if (! $import->legacyDatabaseConfigured()) {
            $this->error('Не задано LEGACY_DB_DATABASE. Додайте з’єднання в .env — див. docs/chat-v2/T13-ETL-STAGING.md');

            return self::FAILURE;
        }

        try {
            $import->assertLegacyReachable();
        } catch (Throwable $e) {
            $this->error('Не вдалося підключитися до legacy: '.$e->getMessage());

            return self::FAILURE;
        }

        $report = $import->inspect();

        $this->info('Legacy: кількість рядків');
        foreach ($report['counts'] as $table => $count) {
            $this->line(sprintf('  %-12s %s', $table, $count >= 0 ? (string) $count : 'n/a'));
        }
        $this->newLine();
        $this->info('Сироти (очікувано 0 після коректного дампу):');
        $this->line('  chat без відповідного user_id у legacy.users: '.$report['orphan_chat_without_user']);
        $this->line('  chat без відповідного room_id у legacy.rooms: '.$report['orphan_chat_without_room']);
        $this->newLine();
        $this->info('T113 — користувачі без публічних постів у legacy.chat (не імпортуються):');
        $this->line('  legacy.users без жодного рядка в chat: '.$report['legacy_users_without_public_chat']);

        return self::SUCCESS;
    }
}
