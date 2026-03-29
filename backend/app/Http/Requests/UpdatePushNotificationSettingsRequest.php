<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePushNotificationSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'web_push_enabled' => ['sometimes', 'boolean'],
            'muted_room_ids' => ['sometimes', 'array'],
            'muted_room_ids.*' => ['integer', 'distinct', 'exists:rooms,room_id'],
            'muted_private_peer_ids' => ['sometimes', 'array'],
            'muted_private_peer_ids.*' => [
                'integer',
                'distinct',
                Rule::exists('users', 'id')->where(function ($q) {
                    $q->where('guest', false);
                }),
            ],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator): void {
            $ids = $this->input('muted_private_peer_ids');
            if (! is_array($ids)) {
                return;
            }
            $user = $this->user();
            if ($user === null) {
                return;
            }
            $userId = (int) $user->id;
            foreach ($ids as $peerId) {
                if ((int) $peerId === $userId) {
                    $validator->errors()->add(
                        'muted_private_peer_ids',
                        'Неможливо вимкнути push для власного акаунту.',
                    );
                    break;
                }
            }
        });
    }
}
