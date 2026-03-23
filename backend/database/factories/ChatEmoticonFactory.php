<?php

namespace Database\Factories;

use App\Models\ChatEmoticon;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<ChatEmoticon>
 */
class ChatEmoticonFactory extends Factory
{
    protected $model = ChatEmoticon::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $code = Str::lower(Str::random(8));

        return [
            'code' => $code,
            'display_name' => 'Smile '.$code,
            'file_name' => $code.'.gif',
            'sort_order' => 0,
            'is_active' => true,
            'keywords' => null,
        ];
    }
}
