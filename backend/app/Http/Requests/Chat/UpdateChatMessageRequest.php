<?php

namespace App\Http\Requests\Chat;

use App\Models\ChatMessage;
use App\Support\ChatMessageBodyStyle;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateChatMessageRequest extends FormRequest
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
            /** @var ChatMessage|null $msg */
            $msg = $this->route('message');
            if (! $msg instanceof ChatMessage) {
                return;
            }

            $text = trim((string) ($this->input('message') ?? ''));
            $hasFile = (int) $msg->file > 0;
            if ($text === '' && ! $hasFile) {
                $validator->errors()->add('message', 'Введіть текст або залиште вкладене зображення.');
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
        });
    }
}
