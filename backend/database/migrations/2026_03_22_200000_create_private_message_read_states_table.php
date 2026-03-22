<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('private_message_read_states', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('peer_id')->constrained('users')->cascadeOnDelete();
            $table->unsignedBigInteger('last_read_incoming_message_id')->default(0);
            $table->timestamps();

            $table->unique(['user_id', 'peer_id'], 'uniq_private_read_user_peer');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('private_message_read_states');
    }
};
