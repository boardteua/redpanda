<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * T26: індекси під стрічку/архів (ORDER BY post_id), приват (OR sender/recipient), перевірку вкладень у ImagePolicy.
 *
 * На великих таблицях MySQL 8 додавання вторинних індексів зазвичай INPLACE / LOCK=NONE;
 * за потреби виконайте еквівалентні ALTER вручну з явними опціями під прод.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('chat', function (Blueprint $table) {
            $table->index(['post_roomid', 'post_id'], 'idx_chat_room_post_id');
            $table->index('file', 'idx_chat_file');
        });

        Schema::table('private_messages', function (Blueprint $table) {
            $table->index(['recipient_id', 'sender_id', 'id'], 'idx_private_recipient_pair_id');
        });
    }

    public function down(): void
    {
        Schema::table('chat', function (Blueprint $table) {
            $table->dropIndex('idx_chat_room_post_id');
            $table->dropIndex('idx_chat_file');
        });

        Schema::table('private_messages', function (Blueprint $table) {
            $table->dropIndex('idx_private_recipient_pair_id');
        });
    }
};
