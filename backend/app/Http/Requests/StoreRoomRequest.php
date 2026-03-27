<?php

namespace App\Http\Requests;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Http\FormRequest;

class StoreRoomRequest extends FormRequest
{
    public function authorize(): bool
    {
        $u = $this->user();

        return $u !== null && ! $u->guest;
    }

    protected function failedAuthorization(): void
    {
        throw new AuthorizationException('Гості не можуть створювати кімнати.');
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'room_name' => ['required', 'string', 'max:191'],
            'slug' => ['sometimes', 'string', 'max:191', 'regex:/^(?=.*[a-zA-Z])[a-zA-Z0-9-]+$/'],
            'topic' => ['sometimes', 'nullable', 'string', 'max:2000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'room_name.required' => 'Вкажіть назву кімнати.',
        ];
    }
}
