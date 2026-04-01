<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('private_messages', function (Blueprint $table) {
            $table->foreignId('image_id')
                ->nullable()
                ->after('body')
                ->constrained('images')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('private_messages', function (Blueprint $table) {
            $table->dropConstrainedForeignId('image_id');
        });
    }
};
