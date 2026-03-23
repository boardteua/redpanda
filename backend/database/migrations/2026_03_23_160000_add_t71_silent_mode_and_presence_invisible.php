<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('chat_settings', function (Blueprint $table) {
            $table->boolean('silent_mode')->default(false)->after('mod_slash_default_kick_minutes');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->boolean('presence_invisible')->default(false)->after('chat_upload_disabled');
        });
    }

    public function down(): void
    {
        Schema::table('chat_settings', function (Blueprint $table) {
            $table->dropColumn('silent_mode');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('presence_invisible');
        });
    }
};
