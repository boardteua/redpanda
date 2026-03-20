<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $isMysql = Schema::getConnection()->getDriverName() === 'mysql';

        Schema::create('chat', function (Blueprint $table) use ($isMysql) {
            $table->bigIncrements('post_id');
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->unsignedInteger('post_date');
            $table->string('post_time', 32)->nullable();
            $table->string('post_user', 191);
            $table->text('post_message');
            $table->string('post_color', 64)->nullable();
            $table->unsignedBigInteger('post_roomid');
            $table->string('type', 32)->default('public');
            $table->string('post_target', 191)->nullable();
            $table->string('avatar', 255)->nullable();
            $table->unsignedBigInteger('file')->default(0);

            $table->foreign('post_roomid')->references('room_id')->on('rooms')->cascadeOnDelete();

            if (! $isMysql) {
                $table->index(['post_roomid', 'post_date'], 'idx_chat_room_date');
            }

            $table->index(['user_id', 'post_date'], 'idx_chat_user_date');
        });

        if ($isMysql) {
            DB::statement('ALTER TABLE chat ADD INDEX idx_chat_room_date (post_roomid, post_date DESC)');
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('chat');
    }
};
