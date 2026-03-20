<?php

namespace App\Services\Moderation;

use App\Models\BannedIp;
use App\Models\FilterWord;
use App\Models\User;
use Illuminate\Support\Facades\Cache;

final class ModerationService
{
    public function banIp(string $ip): BannedIp
    {
        $ip = trim($ip);
        Cache::forget(BannedIp::cacheKeyFor($ip));

        return BannedIp::query()->firstOrCreate(['ip' => $ip]);
    }

    public function unbanIp(int $id): bool
    {
        $row = BannedIp::query()->find($id);
        if ($row === null) {
            return false;
        }
        Cache::forget(BannedIp::cacheKeyFor($row->ip));
        $row->delete();

        return true;
    }

    public function addFilterWord(string $word): FilterWord
    {
        $created = FilterWord::query()->create(['word' => $word]);
        ContentWordFilter::flushCache();

        return $created;
    }

    public function removeFilterWord(int $id): bool
    {
        $deleted = FilterWord::query()->whereKey($id)->delete() > 0;
        if ($deleted) {
            ContentWordFilter::flushCache();
        }

        return $deleted;
    }

    /**
     * @param  int|null  $minutes  null або ≤0 — зняти мут
     */
    public function muteUser(User $target, ?int $minutes): void
    {
        if ($minutes === null || $minutes <= 0) {
            $target->forceFill(['mute_until' => null])->save();

            return;
        }
        $target->forceFill(['mute_until' => time() + $minutes * 60])->save();
    }

    /**
     * @param  int|null  $minutes  null або ≤0 — зняти kick
     */
    public function kickUser(User $target, ?int $minutes): void
    {
        if ($minutes === null || $minutes <= 0) {
            $target->forceFill(['kick_until' => null])->save();

            return;
        }
        $target->forceFill(['kick_until' => time() + $minutes * 60])->save();
    }
}
