<?php

namespace App\Services\Chat;

use App\Models\Room;
use App\Models\RoomSlugHistory;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Transliterator;

class RoomSlugService
{
    /**
     * @return list<string>
     */
    public function reservedSlugs(): array
    {
        /** @var list<string> $list */
        $list = config('chat.reserved_chat_room_slugs', []);

        return array_values(array_unique(array_map(strtolower(...), $list)));
    }

    public function isReserved(string $slug): bool
    {
        return in_array(strtolower($slug), $this->reservedSlugs(), true);
    }

    public function isSlugGloballyTaken(string $slug, ?int $exceptRoomId = null): bool
    {
        $s = strtolower($slug);

        if ($this->isReserved($s)) {
            return true;
        }

        $q = Room::query()->where('slug', $s);
        if ($exceptRoomId !== null) {
            $q->where('room_id', '!=', $exceptRoomId);
        }

        if ($q->exists()) {
            return true;
        }

        return RoomSlugHistory::query()->where('slug', $s)->exists();
    }

    public function proposeUniqueSlugFromName(string $roomName, ?int $exceptRoomId): string
    {
        $base = $this->baseSlugFromRoomName($roomName);
        $n = 0;

        do {
            $candidate = $n === 0 ? $base : $base.'-'.$n;
            $n++;
        } while ($this->isSlugGloballyTaken($candidate, $exceptRoomId));

        return $candidate;
    }

    public function baseSlugFromRoomName(string $roomName): string
    {
        $ascii = $this->transliterateToLatin($roomName);
        $slug = Str::slug($ascii, '-', 'en');
        $slug = strtolower((string) $slug);
        $slug = preg_replace('/[^a-z0-9-]+/', '', $slug) ?? '';
        $slug = trim((string) $slug, '-');

        if ($slug === '') {
            $slug = 'room';
        }

        if (strlen($slug) > 180) {
            $slug = substr($slug, 0, 180);
            $slug = rtrim($slug, '-');
        }

        if ($slug === '') {
            $slug = 'room';
        }

        return $slug;
    }

    public function assertAssignableSlug(string $slug, ?int $exceptRoomId): void
    {
        $s = strtolower($slug);
        if (! preg_match('/^[a-z0-9-]+$/', $s)) {
            throw ValidationException::withMessages([
                'slug' => ['Некоректний формат slug (лише a-z, 0-9, дефіс).'],
            ]);
        }
        if (strlen($s) > 191) {
            throw ValidationException::withMessages([
                'slug' => ['Slug занадто довгий.'],
            ]);
        }
        if ($this->isSlugGloballyTaken($s, $exceptRoomId)) {
            throw ValidationException::withMessages([
                'slug' => ['Цей slug зайнятий або зарезервований.'],
            ]);
        }
    }

    public function recordHistory(int $roomId, string $slug): void
    {
        $s = strtolower($slug);
        if ($s === '') {
            return;
        }

        RoomSlugHistory::query()->firstOrCreate(
            ['slug' => $s],
            ['room_id' => $roomId],
        );
    }

    public function assignSlugAfterRename(Room $room, string $newRoomName): void
    {
        $old = (string) ($room->getOriginal('slug') ?? '');
        if ($old !== '') {
            $this->recordHistory((int) $room->room_id, $old);
        }
        $room->slug = $this->proposeUniqueSlugFromName($newRoomName, (int) $room->room_id);
    }

    public function assignManualSlug(Room $room, string $newSlug): void
    {
        $s = strtolower($newSlug);
        $this->assertAssignableSlug($s, (int) $room->room_id);
        $old = (string) $room->slug;
        if ($old !== '' && $old !== $s) {
            $this->recordHistory((int) $room->room_id, $old);
        }
        $room->slug = $s;
    }

    private function transliterateToLatin(string $value): string
    {
        if (class_exists(Transliterator::class)) {
            $t = Transliterator::create('Any-Latin; Latin-ASCII; [:Nonspacing Mark:] Remove; NFC');
            if ($t !== null) {
                $out = $t->transliterate($value);

                return is_string($out) && $out !== '' ? $out : $value;
            }
        }

        return Str::ascii($value);
    }
}
