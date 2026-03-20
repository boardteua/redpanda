<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('images', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('user_name', 191);
            $table->string('disk_path', 512);
            $table->string('file_name', 255);
            $table->string('mime', 127);
            $table->unsignedInteger('size_bytes');
            $table->unsignedInteger('date_sent');
            $table->index(['user_id', 'date_sent']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('images');
    }
};
