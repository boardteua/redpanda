<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('chat', function (Blueprint $table) {
            $table->index(['moderation_flag_at', 'post_roomid'], 'idx_chat_moderation_flag_room');
        });
    }

    public function down(): void
    {
        Schema::table('chat', function (Blueprint $table) {
            $table->dropIndex('idx_chat_moderation_flag_room');
        });
    }
};
