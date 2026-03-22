<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('chat', function (Blueprint $table) {
            $table->json('post_style')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('chat', function (Blueprint $table) {
            $table->dropColumn('post_style');
        });
    }
};
