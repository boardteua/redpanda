<?php

namespace App\Http\Requests\Chat;

use App\Http\Requests\Chat\Concerns\ValidatesOwnedChatImageId;
use Illuminate\Foundation\Http\FormRequest;

class StorePrivateMessageRequest extends FormRequest
{
    use ValidatesOwnedChatImageId;

    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $user = $this->user();
        $maxLen = 4000;
        if ($user !== null) {
            if ($user->guest) {
                $maxLen = 2000;
            } elseif ($user->isVip() || $user->canModerate()) {
                $maxLen = 8000;
            }
        }

        return [
            'message' => ['nullable', 'string', 'max:'.$maxLen],
            'image_id' => ['nullable', 'integer', 'min:1'],
            'client_message_id' => ['required', 'uuid'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator): void {
            if ($this->filled('image_id')) {
                $this->assertOwnedChatImageId($validator);
            }

            $msg = trim((string) ($this->input('message') ?? ''));
            if ($msg === '' && ! $this->filled('image_id')) {
                $validator->errors()->add('message', 'Введіть текст або додайте зображення.');
            }
        });
    }
}
