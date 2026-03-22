<?php

namespace App\Http\Requests\Me;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class UpdateMeAccountRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        return $user !== null && ! $user->guest;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $user = $this->user();

        return [
            'current_password' => ['required', 'current_password:web'],
            'email' => ['sometimes', 'string', 'lowercase', 'email', 'max:255', 'unique:users,email,'.$user->id],
            'password' => ['sometimes', 'string', 'confirmed', Password::defaults()],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator): void {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }
            $emailChanged = $this->filled('email')
                && (string) $this->input('email') !== (string) $this->user()->email;
            $passwordChange = $this->filled('password');
            if (! $emailChanged && ! $passwordChange) {
                $validator->errors()->add('email', 'Вкажіть нову електронну пошту або новий пароль.');
            }
        });
    }
}
