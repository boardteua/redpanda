<?php

namespace App\Http\Resources;

use App\Models\ChatEmoticon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Публічний каталог смайлів (T63).
 *
 * @mixin ChatEmoticon
 */
class ChatEmoticonResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'code' => $this->code,
            'display_name' => $this->display_name,
            'file' => $this->file_name,
            'keywords' => $this->keywords ?? '',
        ];
    }
}
