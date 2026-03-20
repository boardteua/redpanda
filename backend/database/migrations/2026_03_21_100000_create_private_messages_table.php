<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('private_messages', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->foreignId('sender_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('recipient_id')->constrained('users')->cascadeOnDelete();
            $table->text('body');
            $table->unsignedInteger('sent_at');
            $table->string('sent_time', 32)->nullable();
            $table->uuid('client_message_id');

            $table->unique(['sender_id', 'client_message_id'], 'uniq_private_sender_client');
            $table->index(['sender_id', 'recipient_id', 'id'], 'idx_private_pair_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('private_messages');
    }
};
