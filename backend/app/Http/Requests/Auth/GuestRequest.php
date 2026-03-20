<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class GuestRequest extends FormRequest
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
            'user_name' => [
                'nullable',
                'string',
                'min:2',
                'max:191',
                'regex:/^[\p{L}\p{N}_-]+$/u',
                Rule::unique('users', 'user_name'),
            ],
        ];
    }
}
