<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('chat', function (Blueprint $table) {
            $table->string('client_message_id', 36)->nullable()->after('file');
            $table->unique(['user_id', 'client_message_id'], 'chat_user_client_msg_unique');
        });
    }

    public function down(): void
    {
        Schema::table('chat', function (Blueprint $table) {
            $table->dropUnique('chat_user_client_msg_unique');
            $table->dropColumn('client_message_id');
        });
    }
};
