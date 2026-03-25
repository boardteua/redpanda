<?php

namespace App\Services\LegacyBoardImport;

use Illuminate\Support\Facades\DB;

/**
 * Заміна префіксів URL board.te.ua у текстах після копіювання файлів (**T132**).
 */
final class LegacyBoardTeUaUrlRemapService
{
    /** @return list<string> */
    private function sourcePrefixes(): array
    {
        return [
            'https://www.board.te.ua',
            'http://www.board.te.ua',
            '//www.board.te.ua',
            'https://board.te.ua',
            'http://board.te.ua',
            '//board.te.ua',
        ];
    }

    public function isConfigured(): bool
    {
        $raw = trim((string) config('legacy.url_remap_target_origin', ''));

        return $raw !== '';
    }

    /**
     * @return array{
     *     dry_run: bool,
     *     chat_message_rows: int,
     *     chat_avatar_rows: int,
     *     chat_fields_changed: int,
     *     private_body_rows: int,
     *     private_fields_changed: int,
     * }
     */
    public function remapAll(bool $dryRun): array
    {
        $target = rtrim((string) config('legacy.url_remap_target_origin', ''), '/');
        if ($target === '') {
            throw new \RuntimeException('Задайте LEGACY_URL_REMAP_TARGET_ORIGIN у .env (база без завершального /).');
        }

        $chatMessageRows = (int) DB::table('chat')->where('post_message', 'like', '%board.te.ua%')->count();
        $chatAvatarRows = (int) DB::table('chat')->where('avatar', 'like', '%board.te.ua%')->count();

        $chatChanged = $this->remapColumn(
            'chat',
            'post_id',
            'post_message',
            $target,
            $dryRun
        );
        $chatChanged += $this->remapColumn(
            'chat',
            'post_id',
            'avatar',
            $target,
            $dryRun
        );

        $privateBodyRows = 0;
        $privateChanged = 0;
        if (DB::getSchemaBuilder()->hasTable('private_messages')) {
            $privateBodyRows = (int) DB::table('private_messages')->where('body', 'like', '%board.te.ua%')->count();
            $privateChanged = $this->remapColumn(
                'private_messages',
                'id',
                'body',
                $target,
                $dryRun
            );
        }

        return [
            'dry_run' => $dryRun,
            'chat_message_rows' => $chatMessageRows,
            'chat_avatar_rows' => $chatAvatarRows,
            'chat_fields_changed' => $chatChanged,
            'private_body_rows' => $privateBodyRows,
            'private_fields_changed' => $privateChanged,
        ];
    }

    /**
     * @return int кількість рядків, де значення змінилося (або було б змінене при dry-run)
     */
    private function remapColumn(string $table, string $pk, string $column, string $targetOrigin, bool $dryRun): int
    {
        $updated = 0;
        DB::table($table)
            ->where($column, 'like', '%board.te.ua%')
            ->orderBy($pk)
            ->chunkById(200, function ($rows) use (&$updated, $table, $pk, $column, $targetOrigin, $dryRun): void {
                foreach ($rows as $row) {
                    $raw = (string) ($row->{$column} ?? '');
                    $next = $this->replacePrefixes($raw, $targetOrigin);
                    if ($next === null) {
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

    private function replacePrefixes(string $value, string $targetOrigin): ?string
    {
        $next = $value;
        foreach ($this->sourcePrefixes() as $p) {
            $next = str_replace($p, $targetOrigin, $next);
        }

        return $next === $value ? null : $next;
    }
}
