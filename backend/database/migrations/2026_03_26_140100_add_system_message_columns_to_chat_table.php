<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('chat', function (Blueprint $table): void {
            $table->string('system_kind', 48)->nullable()->after('type');
            $table->unsignedBigInteger('system_target_room_id')->nullable()->after('system_kind');
            $table->string('system_action_label', 191)->nullable()->after('system_target_room_id');

            $table->foreign('system_target_room_id')
                ->references('room_id')
                ->on('rooms')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('chat', function (Blueprint $table): void {
            $table->dropForeign(['system_target_room_id']);
            $table->dropColumn(['system_kind', 'system_target_room_id', 'system_action_label']);
        });
    }
};
