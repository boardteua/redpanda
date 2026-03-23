<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('chat_settings', function (Blueprint $table) {
            $table->json('landing_settings')->nullable();
            $table->json('registration_flags')->nullable();
            $table->boolean('sound_on_every_post')->default(false);
        });
    }

    public function down(): void
    {
        Schema::table('chat_settings', function (Blueprint $table) {
            $table->dropColumn(['landing_settings', 'registration_flags', 'sound_on_every_post']);
        });
    }
};
