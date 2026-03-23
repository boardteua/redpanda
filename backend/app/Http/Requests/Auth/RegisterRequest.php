<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class RegisterRequest extends FormRequest
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
            'user_name' => ['required', 'string', 'min:2', 'max:191', 'unique:users,user_name', 'regex:/^[\p{L}\p{N}_-]+$/u'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'max:1024', 'confirmed', Password::defaults()],
            'password_confirmation' => ['required', 'string', 'max:1024'],
            // Honeypot: легітимний клієнт лишає порожнім або не надсилає поле.
            'department' => ['sometimes', 'nullable', 'string', 'max:0'],
        ];
    }
}
