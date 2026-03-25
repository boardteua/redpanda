<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('chat_settings', function (Blueprint $table): void {
            $table->boolean('message_flood_enabled')->default(false)->after('message_edit_window_hours');
            $table->unsignedSmallInteger('message_flood_max_messages')->default(5)->after('message_flood_enabled');
            $table->unsignedSmallInteger('message_flood_window_seconds')->default(10)->after('message_flood_max_messages');
        });
    }

    public function down(): void
    {
        Schema::table('chat_settings', function (Blueprint $table): void {
            $table->dropColumn([
                'message_flood_enabled',
                'message_flood_max_messages',
                'message_flood_window_seconds',
            ]);
        });
    }
};
