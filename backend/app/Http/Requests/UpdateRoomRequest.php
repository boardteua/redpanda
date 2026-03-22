<?php

namespace App\Http\Requests;

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
            'topic' => ['sometimes', 'nullable', 'string', 'max:2000'],
            'access' => ['sometimes', 'integer', Rule::in([Room::ACCESS_PUBLIC, Room::ACCESS_REGISTERED, Room::ACCESS_VIP])],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $v): void {
            $data = $this->all();
            $has = array_key_exists('room_name', $data)
                || array_key_exists('topic', $data)
                || array_key_exists('access', $data);
            if (! $has) {
                $v->errors()->add('room_name', 'Надайте хоча б одне поле для оновлення.');
            }
        });
    }
}
