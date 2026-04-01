<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('chat_settings', function (Blueprint $table): void {
            $table->boolean('ai_llm_enabled')->default(false);

            // Optional DB overrides for model routing (fallback to config/services.php when null).
            $table->string('ai_gemini_model_flash', 100)->nullable();
            $table->string('ai_gemini_model_flash_lite', 100)->nullable();
            $table->string('ai_gemini_model_pro', 100)->nullable();
            $table->string('ai_gemini_model_image', 100)->nullable();

            // Persona prompt stored in DB (admin-controlled). Revision is used for cache keys.
            $table->text('ai_bot_persona_prompt')->nullable();
            $table->unsignedInteger('ai_bot_persona_revision')->default(1);
        });
    }

    public function down(): void
    {
        Schema::table('chat_settings', function (Blueprint $table): void {
            $table->dropColumn([
                'ai_llm_enabled',
                'ai_gemini_model_flash',
                'ai_gemini_model_flash_lite',
                'ai_gemini_model_pro',
                'ai_gemini_model_image',
                'ai_bot_persona_prompt',
                'ai_bot_persona_revision',
            ]);
        });
    }
};

