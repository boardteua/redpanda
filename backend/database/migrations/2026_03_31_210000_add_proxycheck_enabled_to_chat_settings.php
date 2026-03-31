<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('chat_settings', function (Blueprint $table) {
            $table->boolean('proxycheck_enabled')->default(true)->after('message_flood_window_seconds');
        });

        DB::table('chat_settings')->update(['proxycheck_enabled' => true]);
    }

    public function down(): void
    {
        Schema::table('chat_settings', function (Blueprint $table) {
            $table->dropColumn('proxycheck_enabled');
        });
    }
};

