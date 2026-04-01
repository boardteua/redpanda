<?php

namespace App\Services\Chat;

use App\Models\ChatMessage;
use App\Models\User;
use App\Models\UserIgnore;
use Illuminate\Database\Eloquent\Builder;

/**
 * T196: hide public / inline_private room lines authored by users the viewer ignores.
 * System and other message types stay visible even if user_id matches (edge cases).
 */
final class IgnoredRoomMessageVisibility
{
    /**
     * @param  Builder<ChatMessage>  $query
     */
    public static function scopeExcludeIgnoredAuthors(Builder $query, User $viewer): void
    {
        if ($viewer->guest) {
            return;
        }

        /** @var list<int> $ids */
        $ids = UserIgnore::query()
            ->where('user_id', (int) $viewer->id)
            ->pluck('ignored_user_id')
            ->map(fn ($id) => (int) $id)
            ->values()
            ->all();

        if ($ids === []) {
            return;
        }

        $query->where(function (Builder $q) use ($ids) {
            $q->where(function (Builder $inner) use ($ids) {
                $inner->whereNull('user_id')->orWhereNotIn('user_id', $ids);
            })->orWhereNotIn('type', ['public', 'inline_private']);
        });
    }
}
