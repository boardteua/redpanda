<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('chat_themes', function (Blueprint $table) {
            $table->id();
            $table->string('name', 64);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->unique('name');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('chat_themes');
    }
};
