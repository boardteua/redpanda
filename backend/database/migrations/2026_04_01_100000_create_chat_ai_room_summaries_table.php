<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('chat_ai_room_summaries', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('room_id');
            $table->unsignedBigInteger('summary_until_post_id')->default(0);
            $table->text('summary_text')->nullable();
            $table->timestamps();

            $table->unique('room_id', 'chat_ai_room_summaries_room_unique');
            $table->index(['room_id', 'summary_until_post_id'], 'chat_ai_room_summaries_room_until_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('chat_ai_room_summaries');
    }
};

