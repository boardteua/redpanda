<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('chat', function (Blueprint $table): void {
            $table->index(['post_roomid', 'post_deleted_at', 'post_id'], 'idx_chat_room_visible_post');
            $table->index(['post_roomid', 'type', 'user_id', 'post_id'], 'idx_chat_room_type_user_post');
            $table->index(['post_roomid', 'type', 'post_target', 'post_id'], 'idx_chat_room_type_target_post');
        });
    }

    public function down(): void
    {
        Schema::table('chat', function (Blueprint $table): void {
            $table->dropIndex('idx_chat_room_visible_post');
            $table->dropIndex('idx_chat_room_type_user_post');
            $table->dropIndex('idx_chat_room_type_target_post');
        });
    }
};
