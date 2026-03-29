<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_web_push_room_mutes', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->unsignedBigInteger('room_id');
            $table->timestamps();

            $table->unique(['user_id', 'room_id']);
            $table->foreign('room_id')->references('room_id')->on('rooms')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_web_push_room_mutes');
    }
};
