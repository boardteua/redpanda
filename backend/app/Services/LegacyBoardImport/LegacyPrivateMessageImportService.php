<?php

namespace App\Services\LegacyBoardImport;

use App\Models\PrivateMessage;
use App\Models\User;
use Illuminate\Database\Connection;
use Illuminate\Support\Facades\DB;
use stdClass;
use Throwable;

/**
 * Імпорт legacy таблиці `private` (ніки hunter/target) у `private_messages` (T131).
 */
final class LegacyPrivateMessageImportService
{
    public function legacyPrivateTableExists(): bool
    {
        $legacy = DB::connection('legacy');

        return $this->safeCount($legacy, 'private') >= 0;
    }

    public function assertLegacyReachable(): void
    {
        DB::connection('legacy')->getPdo();
    }

    /**
     * @return array{
     *     dry_run: bool,
     *     legacy_rows: int,
     *     inserted: int,
     *     skipped_no_hunter: int,
     *     skipped_no_target: int,
     *     skipped_self: int,
     *     skipped_empty_body: int,
     *     skipped_bad_time: int,
     * }
     */
    public function import(bool $dryRun): array
    {
        $legacy = DB::connection('legacy');
        if ($this->safeCount($legacy, 'private') < 0) {
            throw new \RuntimeException('У legacy немає таблиці `private`.');
        }

        $total = (int) $legacy->table('private')->count();

        $report = [
            'dry_run' => $dryRun,
            'legacy_rows' => $total,
            'inserted' => 0,
            'skipped_no_hunter' => 0,
            'skipped_no_target' => 0,
            'skipped_self' => 0,
            'skipped_empty_body' => 0,
            'skipped_bad_time' => 0,
        ];

        if ($dryRun) {
            return $report;
        }

        if (PrivateMessage::query()->exists()) {
            throw new \RuntimeException(
                'Таблиця private_messages не порожня. Політика T131 — лише імпорт у порожню таблицю (merge не реалізовано).'
            );
        }

        /** @var array<string, int> */
        $nickToId = [];
        foreach (User::query()->select(['id', 'user_name'])->cursor() as $u) {
            $k = mb_strtolower(trim((string) $u->user_name));
            if ($k !== '' && ! isset($nickToId[$k])) {
                $nickToId[$k] = (int) $u->id;
            }
        }

        $legacy->table('private')->orderBy('id')->chunk(500, function ($chunk) use (&$report, $nickToId): void {
            $batch = [];
            foreach ($chunk as $row) {
                /** @var stdClass $row */
                $stats = $this->mapRowToInsert($row, $nickToId);
                if ($stats['row'] === null) {
                    $report[$stats['skip']]++;
                    continue;
                }
                $batch[] = $stats['row'];
            }
            if ($batch !== []) {
                PrivateMessage::query()->insert($batch);
                $report['inserted'] += count($batch);
            }
        });

        return $report;
    }

    /**
     * @param  array<string, int>  $nickToId
     * @return array{row: ?array<string, mixed>, skip?: string}
     */
    private function mapRowToInsert(stdClass $row, array $nickToId): array
    {
        $hunter = mb_strtolower(trim((string) ($row->hunter ?? '')));
        $target = mb_strtolower(trim((string) ($row->target ?? '')));
        if ($hunter === '') {
            return ['row' => null, 'skip' => 'skipped_no_hunter'];
        }
        if ($target === '') {
            return ['row' => null, 'skip' => 'skipped_no_target'];
        }

        $senderId = $nickToId[$hunter] ?? null;
        $recipientId = $nickToId[$target] ?? null;
        if ($senderId === null) {
            return ['row' => null, 'skip' => 'skipped_no_hunter'];
        }
        if ($recipientId === null) {
            return ['row' => null, 'skip' => 'skipped_no_target'];
        }
        if ($senderId === $recipientId) {
            return ['row' => null, 'skip' => 'skipped_self'];
        }

        $body = (string) ($row->message ?? '');
        if (trim($body) === '') {
            return ['row' => null, 'skip' => 'skipped_empty_body'];
        }

        $sentAt = (int) ($row->time ?? $row->date ?? 0);
        if ($sentAt <= 0) {
            return ['row' => null, 'skip' => 'skipped_bad_time'];
        }

        $legacyId = (int) ($row->id ?? 0);
        if ($legacyId <= 0) {
            return ['row' => null, 'skip' => 'skipped_bad_time'];
        }

        $sentTime = isset($row->display_time) ? mb_substr((string) $row->display_time, 0, 32) : null;

        return [
            'row' => [
                'sender_id' => $senderId,
                'recipient_id' => $recipientId,
                'body' => $body,
                'sent_at' => $sentAt,
                'sent_time' => $sentTime,
                'client_message_id' => $this->legacyPrivateClientMessageId($legacyId),
            ],
        ];
    }

    private function legacyPrivateClientMessageId(int $legacyPrivateId): string
    {
        $hex = md5('redpanda:legacy:private:'.$legacyPrivateId);

        return substr($hex, 0, 8)
            .'-'.substr($hex, 8, 4)
            .'-'.substr($hex, 12, 4)
            .'-'.substr($hex, 16, 4)
            .'-'.substr($hex, 20, 12);
    }

    private function safeCount(Connection $legacy, string $table): int
    {
        try {
            return (int) $legacy->table($table)->count();
        } catch (Throwable) {
            return -1;
        }
    }
}
