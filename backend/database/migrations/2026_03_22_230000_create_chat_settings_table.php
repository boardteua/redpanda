<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('chat_settings', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('room_create_min_public_messages')->default(100);
            $table->string('public_message_count_scope', 64)->default('all_public_rooms');
            $table->unsignedBigInteger('message_count_room_id')->nullable();
            $table->timestamps();

            $table->foreign('message_count_room_id')
                ->references('room_id')
                ->on('rooms')
                ->nullOnDelete();
        });

        DB::table('chat_settings')->insert([
            'room_create_min_public_messages' => 100,
            'public_message_count_scope' => 'all_public_rooms',
            'message_count_room_id' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('chat_settings');
    }
};
