<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('filter_words', function (Blueprint $table): void {
            $table->string('category', 64)->default('default')->after('word');
            $table->string('match_mode', 24)->default('substring')->after('category');
            $table->string('action', 24)->default('mask')->after('match_mode');
            $table->unsignedSmallInteger('mute_minutes')->nullable()->after('action');
        });

        Schema::table('chat', function (Blueprint $table): void {
            $table->unsignedInteger('moderation_flag_at')->nullable()->after('post_deleted_at');
        });
    }

    public function down(): void
    {
        Schema::table('chat', function (Blueprint $table): void {
            $table->dropColumn('moderation_flag_at');
        });

        Schema::table('filter_words', function (Blueprint $table): void {
            $table->dropColumn(['category', 'match_mode', 'action', 'mute_minutes']);
        });
    }
};
