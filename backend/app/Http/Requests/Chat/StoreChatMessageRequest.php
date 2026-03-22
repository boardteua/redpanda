<?php

namespace App\Http\Requests\Chat;

use App\Chat\RoomInlinePrivateParser;
use App\Models\Image;
use App\Support\ChatMessageBodyStyle;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

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
            'client_message_id' => ['required', 'string', 'max:36', 'uuid'],
            'style' => ['nullable', 'array'],
            'style.bold' => ['sometimes', 'boolean'],
            'style.italic' => ['sometimes', 'boolean'],
            'style.underline' => ['sometimes', 'boolean'],
            'style.bg' => ['nullable', 'string', Rule::in(ChatMessageBodyStyle::BG_KEYS)],
            'style.fg' => ['nullable', 'string', Rule::in(ChatMessageBodyStyle::FG_KEYS)],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator): void {
            $user = $this->user();
            if ($user !== null && $user->guest && $this->filled('image_id')) {
                $validator->errors()->add('image_id', 'Гості не можуть додавати зображення до повідомлень.');
            }

            $msg = trim((string) ($this->input('message') ?? ''));
            $inline = RoomInlinePrivateParser::tryParse((string) ($this->input('message') ?? ''));
            if ($inline !== null && $this->filled('image_id')) {
                $validator->errors()->add('image_id', 'Неможливо додати зображення до інлайн-привату /msg.');
            }
            if ($msg === '' && ! $this->filled('image_id')) {
                $validator->errors()->add('message', 'Введіть текст або додайте зображення.');
            }

            $style = $this->input('style');
            if ($style !== null && ! is_array($style)) {
                $validator->errors()->add('style', 'Некоректний формат стилю.');
            } elseif (is_array($style)) {
                $bg = $style['bg'] ?? null;
                $fg = $style['fg'] ?? null;
                if ($bg !== null && $bg !== '' && $fg !== null && $fg !== '') {
                    $validator->errors()->add('style.fg', 'Не можна одночасно задати колір тла та колір тексту.');
                }
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
