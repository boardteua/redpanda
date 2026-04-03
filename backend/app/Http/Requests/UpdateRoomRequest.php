<?php

namespace App\Http\Requests;

use App\Models\ChatSetting;
use App\Models\Room;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdateRoomRequest extends FormRequest
{
    public function authorize(): bool
    {
        $u = $this->user();

        return $u !== null && ! $u->guest;
    }

    protected function failedAuthorization(): void
    {
        throw new AuthorizationException('Гості не можуть змінювати кімнати.');
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'room_name' => ['sometimes', 'string', 'min:1', 'max:191'],
            'slug' => ['sometimes', 'string', 'max:191', 'regex:/^(?=.*[a-zA-Z])[a-zA-Z0-9-]+$/'],
            'topic' => ['sometimes', 'nullable', 'string', 'max:2000'],
            'access' => ['sometimes', 'integer', Rule::in([Room::ACCESS_PUBLIC, Room::ACCESS_REGISTERED, Room::ACCESS_VIP])],
            'ai_bot_enabled' => ['sometimes', 'boolean'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $v): void {
            $data = $this->all();
            $has = array_key_exists('room_name', $data)
                || array_key_exists('slug', $data)
                || array_key_exists('topic', $data)
                || array_key_exists('access', $data)
                || array_key_exists('ai_bot_enabled', $data);
            if (! $has) {
                $v->errors()->add('room_name', 'Надайте хоча б одне поле для оновлення.');
            }

            // T198: cannot persist room LLM on while global master switch is off.
            if ($this->has('ai_bot_enabled') && $this->boolean('ai_bot_enabled')) {
                if (! ChatSetting::current()->ai_llm_enabled) {
                    $v->errors()->add(
                        'ai_bot_enabled',
                        'LLM глобально вимкнено в налаштуваннях чату; неможливо увімкнути кімнатного бота.',
                    );
                }
            }
        });
    }
}
