<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('chat_settings', function (Blueprint $table): void {
            $table->string('transactional_mail_from_name', 120)->nullable()->after('max_attachment_bytes');
            $table->json('mail_template_overrides')->nullable()->after('transactional_mail_from_name');
        });
    }

    public function down(): void
    {
        Schema::table('chat_settings', function (Blueprint $table): void {
            $table->dropColumn(['transactional_mail_from_name', 'mail_template_overrides']);
        });
    }
};
