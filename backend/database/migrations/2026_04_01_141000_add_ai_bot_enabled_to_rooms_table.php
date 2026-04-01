<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('rooms', function (Blueprint $table): void {
            $table->boolean('ai_bot_enabled')->default(true)->after('access');
        });
    }

    public function down(): void
    {
        Schema::table('rooms', function (Blueprint $table): void {
            $table->dropColumn('ai_bot_enabled');
        });
    }
};

