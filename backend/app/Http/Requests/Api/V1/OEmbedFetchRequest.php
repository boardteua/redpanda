<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class OEmbedFetchRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'url' => ['required', 'string', 'max:2048'],
            'maxwidth' => ['sometimes', 'nullable', 'integer', 'min:1', 'max:2000'],
            'maxheight' => ['sometimes', 'nullable', 'integer', 'min:1', 'max:2000'],
        ];
    }
}
