<?php

namespace App\Http\Requests\Chat\Concerns;

use App\Models\Image;
use Illuminate\Validation\Validator;

/**
 * Спільна перевірка `image_id` для публічних і приватних повідомлень (T10 / T195).
 */
trait ValidatesOwnedChatImageId
{
    /**
     * Гість / upoff та власність зображення. Викликати з `after` лише коли `image_id` передано.
     */
    protected function assertOwnedChatImageId(Validator $validator): void
    {
        $user = $this->user();
        if ($user !== null && $user->guest && $this->filled('image_id')) {
            $validator->errors()->add('image_id', 'Гості не можуть додавати зображення до повідомлень.');
        }
        if ($user !== null && ! $user->guest && $this->filled('image_id') && $user->isChatUploadDisabled()) {
            $validator->errors()->add('image_id', 'Завантаження зображень для вашого облікового запису вимкнено модератором.');
        }

        if (! $this->filled('image_id')) {
            return;
        }

        $uid = (int) $this->user()->id;
        $owns = Image::query()
            ->where('id', (int) $this->input('image_id'))
            ->where('user_id', $uid)
            ->exists();
        if (! $owns) {
            $validator->errors()->add('image_id', 'Невірне зображення.');
        }
    }
}
