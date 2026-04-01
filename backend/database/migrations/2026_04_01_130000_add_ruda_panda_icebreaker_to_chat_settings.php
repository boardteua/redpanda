<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('chat_settings', function (Blueprint $table): void {
            $table->boolean('ai_icebreaker_enabled')->default(false);
            $table->unsignedSmallInteger('ai_icebreaker_idle_minutes')->default(60);
            $table->unsignedSmallInteger('ai_icebreaker_cooldown_minutes')->default(180);
            $table->unsignedSmallInteger('ai_icebreaker_jitter_minutes')->default(10);
        });
    }

    public function down(): void
    {
        Schema::table('chat_settings', function (Blueprint $table): void {
            $table->dropColumn([
                'ai_icebreaker_enabled',
                'ai_icebreaker_idle_minutes',
                'ai_icebreaker_cooldown_minutes',
                'ai_icebreaker_jitter_minutes',
            ]);
        });
    }
};
