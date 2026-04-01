<?php

namespace App\Policies;

use App\Models\ChatSetting;
use App\Models\Room;
use App\Models\User;
use App\Services\Chat\UserPublicMessageCountService;

class RoomPolicy
{
    /**
     * Створення нової кімнати (**T44**): гість — ні; staff/VIP — так; зареєстрований — якщо лічильник публічних повідомлень > N з {@see ChatSetting}.
     */
    public function create(User $user): bool
    {
        if ($user->guest) {
            return false;
        }

        if ($user->canModerate() || $user->isVip()) {
            return true;
        }

        $settings = ChatSetting::current();
        $n = (int) $settings->room_create_min_public_messages;
        $count = app(UserPublicMessageCountService::class)->countEligiblePublicMessages($user, $settings);

        return $count > $n;
    }

    /**
     * `access === 0` — будь-який авторизований користувач (у т. ч. гість).
     * `access === 1` — лише зареєстрований обліковий запис.
     * `access >= 2` (VIP-кімната) — зареєстрований з VIP або персонал модерації.
     */
    public function interact(User $user, Room $room): bool
    {
        $access = (int) $room->access;

        if ($access === Room::ACCESS_PUBLIC) {
            return true;
        }

        if ($user->guest) {
            return false;
        }

        if ($access >= Room::ACCESS_VIP) {
            return $user->canAccessVipRooms();
        }

        return true;
    }

    /**
     * Оновлення назви та опису (**T54**): творець кімнати або модератор/адмін; legacy без творця — лише staff.
     */
    public function updateDetails(User $user, Room $room): bool
    {
        if ($user->guest) {
            return false;
        }

        if ($user->canModerate()) {
            return true;
        }

        if ($room->created_by_user_id === null) {
            return false;
        }

        return (int) $room->created_by_user_id === (int) $user->id;
    }

    /**
     * Зміна рівня доступу (VIP тощо) — лише персонал модерації (**T54**).
     */
    public function updateAccess(User $user, Room $room): bool
    {
        return ! $user->guest && $user->canModerate();
    }

    /**
     * Увімкнення LLM «Рудої Панди» у кімнаті (**T184**) — лише адміністратор чату.
     */
    public function updateChatAiBot(User $user, Room $room): bool
    {
        return $user->isChatAdmin();
    }

    /**
     * Видалення порожньої кімнати: творець або staff (**T54**); наявність повідомлень перевіряється в контролері (422).
     */
    public function delete(User $user, Room $room): bool
    {
        if ($user->guest) {
            return false;
        }

        if ($user->canModerate()) {
            return true;
        }

        if ($room->created_by_user_id === null) {
            return false;
        }

        return (int) $room->created_by_user_id === (int) $user->id;
    }
}
