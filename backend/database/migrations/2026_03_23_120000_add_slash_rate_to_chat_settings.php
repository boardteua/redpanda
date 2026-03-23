<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('chat_settings', function (Blueprint $table): void {
            $table->unsignedSmallInteger('slash_command_max_per_window')->default(45);
            $table->unsignedSmallInteger('slash_command_window_seconds')->default(60);
        });
    }

    public function down(): void
    {
        Schema::table('chat_settings', function (Blueprint $table): void {
            $table->dropColumn([
                'slash_command_max_per_window',
                'slash_command_window_seconds',
            ]);
        });
    }
};
