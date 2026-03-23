<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const DEFAULT_BYTES = 4 * 1024 * 1024;

    public function up(): void
    {
        Schema::table('chat_settings', function (Blueprint $table): void {
            $table->unsignedInteger('max_attachment_bytes')
                ->default(self::DEFAULT_BYTES)
                ->after('sound_on_every_post');
        });

        DB::table('chat_settings')->update([
            'max_attachment_bytes' => self::DEFAULT_BYTES,
        ]);
    }

    public function down(): void
    {
        Schema::table('chat_settings', function (Blueprint $table): void {
            $table->dropColumn('max_attachment_bytes');
        });
    }
};
