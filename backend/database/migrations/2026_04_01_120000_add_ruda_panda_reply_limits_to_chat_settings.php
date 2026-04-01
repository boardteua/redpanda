<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('chat_settings', function (Blueprint $table): void {
            $table->unsignedSmallInteger('ai_bot_reply_delay_min_ms')->default(1200);
            $table->unsignedSmallInteger('ai_bot_reply_delay_max_ms')->default(3000);
            $table->unsignedSmallInteger('ai_bot_room_max_replies_per_window')->default(3);
            $table->unsignedSmallInteger('ai_bot_room_window_seconds')->default(300);
            $table->unsignedSmallInteger('ai_bot_global_max_replies_per_window')->default(30);
            $table->unsignedSmallInteger('ai_bot_global_window_seconds')->default(300);
            $table->unsignedSmallInteger('ai_bot_max_reply_chars')->default(500);
        });
    }

    public function down(): void
    {
        Schema::table('chat_settings', function (Blueprint $table): void {
            $table->dropColumn([
                'ai_bot_reply_delay_min_ms',
                'ai_bot_reply_delay_max_ms',
                'ai_bot_room_max_replies_per_window',
                'ai_bot_room_window_seconds',
                'ai_bot_global_max_replies_per_window',
                'ai_bot_global_window_seconds',
                'ai_bot_max_reply_chars',
            ]);
        });
    }
};

