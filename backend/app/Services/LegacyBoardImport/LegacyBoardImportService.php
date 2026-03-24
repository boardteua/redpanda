<?php

namespace App\Services\LegacyBoardImport;

use App\Models\Room;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Connection;
use Illuminate\Support\Facades\DB;
use stdClass;
use Throwable;

/**
 * Читання legacy БД (board.te.ua / org100h) та імпорт у порожню схему redpanda (T13).
 *
 * Секрети та шлях до дампу — лише в .env / поза репо; у репозиторій не потрапляють.
 */
final class LegacyBoardImportService
{
    /** @var array<string, true> */
    private array $importSeenEmails = [];

    public function legacyDatabaseConfigured(): bool
    {
        $name = config('database.connections.legacy.database');

        return is_string($name) && $name !== '';
    }

    public function assertLegacyReachable(): void
    {
        DB::connection('legacy')->getPdo();
    }

    /**
     * @return array{
     *     counts: array<string, int>,
     *     orphan_chat_without_user: int,
     *     orphan_chat_without_room: int,
     *     legacy_users_without_public_chat: int,
     * }
     */
    public function inspect(): array
    {
        $legacy = DB::connection('legacy');

        $counts = [
            'users' => (int) $legacy->table('users')->count(),
            'chat' => (int) $legacy->table('chat')->count(),
            'rooms' => (int) $legacy->table('rooms')->count(),
            'private' => $this->safeCount($legacy, 'private'),
            'friends' => $this->safeCount($legacy, 'friends'),
            'images' => $this->safeCount($legacy, 'images'),
        ];

        $orphanUser = (int) $legacy->selectOne(
            'SELECT COUNT(*) AS c FROM chat c LEFT JOIN users u ON c.user_id = u.user_id WHERE u.user_id IS NULL'
        )->c;

        $orphanRoom = (int) $legacy->selectOne(
            'SELECT COUNT(*) AS c FROM chat c LEFT JOIN rooms r ON c.post_roomid = r.room_id WHERE r.room_id IS NULL'
        )->c;

        $usersWithoutPublicChat = (int) $legacy->selectOne(
            'SELECT COUNT(*) AS c FROM users u WHERE NOT EXISTS (SELECT 1 FROM chat c WHERE c.user_id = u.user_id)'
        )->c;

        return [
            'counts' => $counts,
            'orphan_chat_without_user' => $orphanUser,
            'orphan_chat_without_room' => $orphanRoom,
            'legacy_users_without_public_chat' => $usersWithoutPublicChat,
        ];
    }

    /**
     * @return array{
     *     dry_run: bool,
     *     rooms: int,
     *     users: int,
     *     users_legacy_total: int,
     *     users_skipped_no_posts: int,
     *     stubs: int,
     *     chat_rows: int,
     *     chat_skipped: int,
     * }
     */
    public function importStaging(bool $dryRun): array
    {
        $driver = DB::connection()->getDriverName();
        if (! in_array($driver, ['mysql', 'mariadb'], true)) {
            throw new \RuntimeException('Імпорт підтримує лише MySQL/MariaDB на основному з’єднанні (зараз: '.$driver.').');
        }

        $legacy = DB::connection('legacy');
        $now = Carbon::now();

        $roomRows = $legacy->table('rooms')->orderBy('room_id')->get();
        $allLegacyUsers = $legacy->table('users')->orderBy('user_id')->get();
        $usersLegacyTotal = $allLegacyUsers->count();

        $chatTotal = (int) $legacy->table('chat')->count();
        $distinctChatUsers = $legacy->table('chat')->select('user_id')->distinct()->pluck('user_id')->all();
        [$userRows, $usersSkippedNoPosts] = LegacyImportUserSelection::usersHavingPublicChatPosts(
            $allLegacyUsers,
            $distinctChatUsers
        );
        $legacyUserIdSet = $allLegacyUsers->keyBy('user_id');
        $missingForChat = [];
        foreach ($distinctChatUsers as $uid) {
            $uid = (int) $uid;
            if (! $legacyUserIdSet->has($uid)) {
                $missingForChat[$uid] = true;
            }
        }
        $stubCount = count($missingForChat);

        if ($dryRun) {
            return [
                'dry_run' => true,
                'rooms' => $roomRows->count(),
                'users' => $userRows->count(),
                'users_legacy_total' => $usersLegacyTotal,
                'users_skipped_no_posts' => $usersSkippedNoPosts,
                'stubs' => $stubCount,
                'chat_rows' => $chatTotal,
                'chat_skipped' => 0,
            ];
        }

        if (! $this->targetAppTablesEmpty()) {
            throw new \RuntimeException(
                'Цільова БД має бути порожньою для users/chat/rooms. Виконайте `php artisan migrate:fresh` на окремій staging-схемі (без сидів, що додають кімнати).'
            );
        }

        $this->importSeenEmails = [];

        $report = [
            'dry_run' => false,
            'rooms' => 0,
            'users' => 0,
            'users_legacy_total' => $usersLegacyTotal,
            'users_skipped_no_posts' => $usersSkippedNoPosts,
            'stubs' => 0,
            'chat_rows' => 0,
            'chat_skipped' => 0,
        ];

        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        try {
            foreach ($roomRows as $r) {
                /** @var stdClass $r */
                DB::table('rooms')->insert([
                    'room_id' => (int) $r->room_id,
                    'room_name' => mb_substr((string) $r->room_name, 0, 255),
                    'topic' => $this->nullableString($r->topic ?? ''),
                    'access' => (int) $r->access,
                    'created_at' => $now,
                    'updated_at' => $now,
                    'created_by_user_id' => null,
                ]);
                $report['rooms']++;
            }

            foreach ($userRows as $u) {
                /** @var stdClass $u */
                $this->insertLegacyUserRow($u, $now);
                $report['users']++;
            }

            foreach (array_keys($missingForChat) as $missingId) {
                $this->insertStubUser((int) $missingId, $now);
                $report['stubs']++;
            }

            $legacy->table('chat')->orderBy('post_id')->chunk(500, function ($chunk) use (&$report): void {
                $batch = [];
                foreach ($chunk as $row) {
                    /** @var stdClass $row */
                    if (! $this->roomExistsInApp((int) $row->post_roomid)) {
                        $report['chat_skipped']++;

                        continue;
                    }
                    if (! $this->userExistsInApp((int) $row->user_id)) {
                        $report['chat_skipped']++;

                        continue;
                    }
                    $batch[] = [
                        'post_id' => (int) $row->post_id,
                        'user_id' => (int) $row->user_id,
                        'post_date' => (int) $row->post_date,
                        'post_time' => mb_substr((string) $row->post_time, 0, 32),
                        'post_user' => mb_substr((string) $row->post_user, 0, 191),
                        'post_message' => (string) $row->post_message,
                        'post_color' => mb_substr((string) $row->post_color, 0, 64),
                        'post_roomid' => (int) $row->post_roomid,
                        'type' => mb_substr((string) ($row->type ?: 'public'), 0, 32),
                        'post_target' => $this->nullableString($row->post_target ?? ''),
                        'avatar' => $this->nullableString($row->avatar ?? ''),
                        'file' => 0,
                        'client_message_id' => null,
                        'post_style' => null,
                        'post_edited_at' => null,
                        'post_deleted_at' => null,
                        'moderation_flag_at' => null,
                    ];
                }
                if ($batch !== []) {
                    DB::table('chat')->insert($batch);
                    $report['chat_rows'] += count($batch);
                }
            });

            $this->fixAutoIncrements();
        } finally {
            DB::statement('SET FOREIGN_KEY_CHECKS=1');
        }

        return $report;
    }

    private function targetAppTablesEmpty(): bool
    {
        return ! DB::table('chat')->exists()
            && ! DB::table('users')->exists()
            && ! DB::table('rooms')->exists();
    }

    private function roomExistsInApp(int $roomId): bool
    {
        return Room::query()->where('room_id', $roomId)->exists();
    }

    private function userExistsInApp(int $userId): bool
    {
        return User::query()->where('id', $userId)->exists();
    }

    private function insertLegacyUserRow(stdClass $lu, Carbon $now): void
    {
        $guest = ((int) $lu->guest) !== 0;
        $email = trim((string) ($lu->user_email ?? ''));
        $email = $email === '' ? null : mb_substr($email, 0, 191);
        $email = $this->dedupeEmailForImport($email);

        DB::table('users')->insert([
            'id' => (int) $lu->user_id,
            'user_name' => mb_substr((string) $lu->user_name, 0, 191),
            'email' => $email,
            'email_verified_at' => null,
            'password' => $this->legacyPasswordForStorage((string) ($lu->user_password ?? ''), $guest),
            'guest' => $guest,
            'remember_token' => null,
            'created_at' => $now,
            'updated_at' => $now,
            'user_rank' => $this->mapLegacyRank((int) ($lu->user_rank ?? 1), $guest),
            'mute_until' => null,
            'kick_until' => null,
            'avatar_image_id' => null,
            'vip' => false,
            'profile_country' => $this->nullableProfileString($lu->country ?? ''),
            'profile_region' => $this->nullableProfileString($lu->region ?? ''),
            'profile_age' => $this->nullablePositiveInt($lu->user_age ?? 0),
            'profile_sex' => $this->mapLegacySex((int) ($lu->user_sex ?? 0)),
            'profile_country_hidden' => false,
            'profile_region_hidden' => false,
            'profile_age_hidden' => false,
            'profile_sex_hidden' => false,
            'profile_occupation' => $this->nullableProfileString($lu->custom1 ?? ''),
            'profile_about' => $this->nullableProfileString($lu->user_description ?? ''),
            'social_links' => null,
            'notification_sound_prefs' => null,
            'account_disabled_at' => null,
            'chat_upload_disabled' => false,
            'presence_invisible' => false,
            'auth0_subject' => null,
        ]);
    }

    private function insertStubUser(int $userId, Carbon $now): void
    {
        DB::table('users')->insert([
            'id' => $userId,
            'user_name' => 'legacy_uid_'.$userId,
            'email' => null,
            'email_verified_at' => null,
            'password' => null,
            'guest' => true,
            'remember_token' => null,
            'created_at' => $now,
            'updated_at' => $now,
            'user_rank' => User::RANK_USER,
            'mute_until' => null,
            'kick_until' => null,
            'avatar_image_id' => null,
            'vip' => false,
            'profile_country' => null,
            'profile_region' => null,
            'profile_age' => null,
            'profile_sex' => null,
            'profile_country_hidden' => false,
            'profile_region_hidden' => false,
            'profile_age_hidden' => false,
            'profile_sex_hidden' => false,
            'profile_occupation' => null,
            'profile_about' => null,
            'social_links' => null,
            'notification_sound_prefs' => null,
            'account_disabled_at' => null,
            'chat_upload_disabled' => false,
            'presence_invisible' => false,
            'auth0_subject' => null,
        ]);
    }

    private function legacyPasswordForStorage(string $legacyPassword, bool $guest): ?string
    {
        if ($guest) {
            return null;
        }
        $t = trim($legacyPassword);
        if ($t === '' || strcasecmp($t, 'new') === 0 || ! str_starts_with($t, '$2y$')) {
            return null;
        }

        return mb_substr($t, 0, 255);
    }

    private function mapLegacyRank(int $legacyRank, bool $guest): int
    {
        if ($guest) {
            return User::RANK_USER;
        }
        if ($legacyRank >= 5) {
            return User::RANK_ADMIN;
        }
        if ($legacyRank === 3) {
            return User::RANK_MODERATOR;
        }

        return User::RANK_USER;
    }

    private function mapLegacySex(int $legacySex): ?int
    {
        return $legacySex > 0 ? $legacySex : null;
    }

    private function nullableString(string $value): ?string
    {
        $value = trim($value);

        return $value === '' ? null : $value;
    }

    private function nullableProfileString(string $value): ?string
    {
        $value = trim($value);

        return $value === '' ? null : mb_substr($value, 0, 255);
    }

    private function nullablePositiveInt(mixed $value): ?int
    {
        $i = (int) $value;

        return $i > 0 ? $i : null;
    }

    private function fixAutoIncrements(): void
    {
        $maxUser = (int) DB::table('users')->max('id');
        $maxPost = (int) DB::table('chat')->max('post_id');
        $maxRoom = (int) DB::table('rooms')->max('room_id');

        if ($maxUser > 0) {
            DB::statement('ALTER TABLE users AUTO_INCREMENT = '.($maxUser + 1));
        }
        if ($maxPost > 0) {
            DB::statement('ALTER TABLE chat AUTO_INCREMENT = '.($maxPost + 1));
        }
        if ($maxRoom > 0) {
            DB::statement('ALTER TABLE rooms AUTO_INCREMENT = '.($maxRoom + 1));
        }
    }

    private function safeCount(Connection $legacy, string $table): int
    {
        try {
            return (int) $legacy->table($table)->count();
        } catch (Throwable) {
            return -1;
        }
    }

    private function dedupeEmailForImport(?string $email): ?string
    {
        if ($email === null) {
            return null;
        }
        $k = mb_strtolower($email);
        if (isset($this->importSeenEmails[$k])) {
            return null;
        }
        $this->importSeenEmails[$k] = true;

        return $email;
    }
}
