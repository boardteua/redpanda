<?php

namespace App\Console\Commands;

use App\Services\LegacyBoardImport\LegacyBoardTeUaUrlRemapService;
use Illuminate\Console\Command;

class ChatLegacyRemapBoardUrlsCommand extends Command
{
    protected $signature = 'chat:legacy-remap-board-urls
                            {--dry-run : Лише підрахунок рядків для потенційної заміни}
                            {--force : Дозволити на production (небезпечно)}';

    protected $description = 'Ремап URL board.te.ua / staging-хоста у chat/private_messages (T132/T136; після rsync медіа)';

    public function handle(LegacyBoardTeUaUrlRemapService $remap): int
    {
        if (app()->environment('production') && ! $this->option('force')) {
            $this->error('На production потрібен прапорець --force (і бекап БД). Див. docs/chat-v2/T132-LEGACY-MEDIA-MIGRATION.md');

            return self::FAILURE;
        }

        if (! $remap->isConfigured()) {
            $this->error('Задайте LEGACY_URL_REMAP_TARGET_ORIGIN у .env (приклад у .env.example, T132).');

            return self::FAILURE;
        }

        $dry = (bool) $this->option('dry-run');
        try {
            $r = $remap->remapAll($dry);
        } catch (\Throwable $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        $this->info($dry ? 'Dry-run (БД не змінювалась):' : 'Ремап виконано:');
        $this->line('  chat.post_message (рядки з board.te.ua): '.$r['chat_message_rows']);
        $this->line('  chat.avatar (рядки з board.te.ua): '.$r['chat_avatar_rows']);
        $this->line('  chat: змінено полів (сума post_message + avatar): '.$r['chat_fields_changed']);
        $this->line('  private_messages.body (рядки з board.te.ua): '.$r['private_body_rows']);
        $this->line('  private_messages: змінено полів body: '.$r['private_fields_changed']);

        return self::SUCCESS;
    }
}
