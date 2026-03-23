<?php

namespace App\Http\Resources;

use App\Models\ChatEmoticon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Адмінський перегляд запису смайла (T63).
 *
 * @mixin ChatEmoticon
 */
class ChatEmoticonAdminResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'code' => $this->code,
            'display_name' => $this->display_name,
            'file' => $this->file_name,
            'sort_order' => (int) $this->sort_order,
            'is_active' => (bool) $this->is_active,
            'keywords' => $this->keywords,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
