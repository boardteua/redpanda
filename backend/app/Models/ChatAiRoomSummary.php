<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

final class ChatAiRoomSummary extends Model
{
    protected $table = 'chat_ai_room_summaries';

    protected $fillable = [
        'room_id',
        'summary_until_post_id',
        'summary_text',
    ];

    protected $casts = [
        'room_id' => 'int',
        'summary_until_post_id' => 'int',
    ];
}

