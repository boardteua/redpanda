<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('chat_settings', function (Blueprint $table): void {
            $table->unsignedSmallInteger('ai_summary_window_hours')->default(3);
            $table->unsignedSmallInteger('ai_summary_rollup_chunk_size')->default(30);
            $table->unsignedSmallInteger('ai_summary_max_chars')->default(8000);
        });
    }

    public function down(): void
    {
        Schema::table('chat_settings', function (Blueprint $table): void {
            $table->dropColumn([
                'ai_summary_window_hours',
                'ai_summary_rollup_chunk_size',
                'ai_summary_max_chars',
            ]);
        });
    }
};

