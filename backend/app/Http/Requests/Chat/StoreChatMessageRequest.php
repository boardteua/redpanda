<?php

namespace App\Http\Requests\Chat;

use App\Models\Image;
use Illuminate\Foundation\Http\FormRequest;

class StoreChatMessageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'message' => ['nullable', 'string', 'max:4000'],
            'image_id' => ['nullable', 'integer', 'min:1'],
            'client_message_id' => ['required', 'string', 'max:36', 'uuid'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator): void {
            $msg = trim((string) ($this->input('message') ?? ''));
            if ($msg === '' && ! $this->filled('image_id')) {
                $validator->errors()->add('message', 'Введіть текст або додайте зображення.');
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
        });
    }
}
