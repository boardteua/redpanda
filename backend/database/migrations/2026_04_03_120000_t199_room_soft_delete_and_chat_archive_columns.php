<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * T199: soft-delete кімнати; мітка архіву для дописів з видаленої кімнати.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('rooms', function (Blueprint $table): void {
            $table->softDeletes();
        });

        Schema::table('chat', function (Blueprint $table): void {
            $table->unsignedBigInteger('archived_from_room_id')->nullable()->after('post_roomid');
            $table->string('archived_room_name', 191)->nullable()->after('archived_from_room_id');
            $table->unsignedTinyInteger('archived_room_access')->nullable()->after('archived_room_name');

            $table->index('archived_from_room_id');
        });
    }

    public function down(): void
    {
        Schema::table('chat', function (Blueprint $table): void {
            $table->dropIndex(['archived_from_room_id']);
            $table->dropColumn(['archived_from_room_id', 'archived_room_name', 'archived_room_access']);
        });

        Schema::table('rooms', function (Blueprint $table): void {
            $table->dropSoftDeletes();
        });
    }
};
