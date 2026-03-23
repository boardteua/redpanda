<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('chat_settings', function (Blueprint $table): void {
            $table->unsignedSmallInteger('mod_slash_default_mute_minutes')->default(30)->after('slash_command_window_seconds');
            $table->unsignedSmallInteger('mod_slash_default_kick_minutes')->default(60)->after('mod_slash_default_mute_minutes');
        });
    }

    public function down(): void
    {
        Schema::table('chat_settings', function (Blueprint $table): void {
            $table->dropColumn(['mod_slash_default_mute_minutes', 'mod_slash_default_kick_minutes']);
        });
    }
};
