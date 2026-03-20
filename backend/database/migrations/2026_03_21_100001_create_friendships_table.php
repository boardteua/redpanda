<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('friendships', function (Blueprint $table) {
            $table->id();
            $table->foreignId('requester_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('addressee_id')->constrained('users')->cascadeOnDelete();
            $table->string('status', 32);
            $table->timestamps();

            $table->unique(['requester_id', 'addressee_id'], 'uniq_friendship_pair');
            $table->index(['addressee_id', 'status'], 'idx_friendship_addressee_status');
            $table->index(['requester_id', 'status'], 'idx_friendship_requester_status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('friendships');
    }
};
