<?php

namespace App\Console\Commands;

use App\Support\LegacyChatHtmlGarbageCleaner;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ChatSanitizeImportedHtmlCommand extends Command
{
    protected $signature = 'chat:sanitize-imported-html
                            {--dry-run : Лише підрахунок рядків, де текст зміниться}
                            {--force : Дозволити на production}
                            {--chunk=200 : Розмір chunk для обходу таблиць}';

    protected $description = 'Очистка legacy HTML: junk span, зламані fancybox; зняти обгортку fancybox, коли href=src (chat, private_messages)';

    public function handle(LegacyChatHtmlGarbageCleaner $cleaner): int
    {
        if (app()->environment('production') && ! $this->option('force')) {
            $this->error('На production потрібен --force (бекап БД).');

            return self::FAILURE;
        }

        $chunk = max(1, min(2000, (int) $this->option('chunk')));
        $dry = (bool) $this->option('dry-run');

        $chatUpdated = $this->sanitizeTable(
            $cleaner,
            'chat',
            'post_id',
            'post_message',
            $chunk,
            $dry
        );

        $privateUpdated = 0;
        if (DB::getSchemaBuilder()->hasTable('private_messages')) {
            $privateUpdated = $this->sanitizeTable(
                $cleaner,
                'private_messages',
                'id',
                'body',
                $chunk,
                $dry
            );
        }

        $this->info($dry ? 'Dry-run (БД не змінювалась):' : 'Очищення виконано:');
        $this->line('  chat.post_message (рядків змінено / знайдено): '.$chatUpdated);
        $this->line('  private_messages.body (рядків змінено / знайдено): '.$privateUpdated);

        return self::SUCCESS;
    }

    private function sanitizeTable(
        LegacyChatHtmlGarbageCleaner $cleaner,
        string $table,
        string $pk,
        string $column,
        int $chunk,
        bool $dryRun,
    ): int {
        $updated = 0;
        DB::table($table)
            ->where(function ($q) use ($column): void {
                $q->where(function ($q2) use ($column): void {
                    $q2->where($column, 'like', '%<span%')
                        ->where($column, 'like', '%color:%')
                        ->where($column, 'like', '%background:%');
                })->orWhere(function ($q2) use ($column): void {
                    $q2->where($column, 'like', '%fancybox%')
                        ->where($column, 'like', '%<a%')
                        ->where($column, 'like', '%<img%');
                });
            })
            ->orderBy($pk)
            ->chunkById($chunk, function ($rows) use (&$updated, $cleaner, $table, $pk, $column, $dryRun): void {
                foreach ($rows as $row) {
                    $raw = (string) ($row->{$column} ?? '');
                    $next = $cleaner->clean($raw);
                    if ($next === $raw) {
                        continue;
                    }
                    $updated++;
                    if (! $dryRun) {
                        DB::table($table)->where($pk, $row->{$pk})->update([$column => $next]);
                    }
                }
            }, $pk);

        return $updated;
    }
}
