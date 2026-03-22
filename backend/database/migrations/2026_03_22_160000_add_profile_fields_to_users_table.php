<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('profile_country', 100)->nullable()->after('avatar_image_id');
            $table->string('profile_region', 100)->nullable()->after('profile_country');
            $table->unsignedTinyInteger('profile_age')->nullable()->after('profile_region');
            $table->string('profile_sex', 32)->nullable()->after('profile_age');
            $table->boolean('profile_country_hidden')->default(false)->after('profile_sex');
            $table->boolean('profile_region_hidden')->default(false)->after('profile_country_hidden');
            $table->boolean('profile_age_hidden')->default(false)->after('profile_region_hidden');
            $table->boolean('profile_sex_hidden')->default(false)->after('profile_age_hidden');
            $table->string('profile_occupation', 191)->nullable()->after('profile_sex_hidden');
            $table->text('profile_about')->nullable()->after('profile_occupation');
            $table->json('social_links')->nullable()->after('profile_about');
            $table->json('notification_sound_prefs')->nullable()->after('social_links');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'profile_country',
                'profile_region',
                'profile_age',
                'profile_sex',
                'profile_country_hidden',
                'profile_region_hidden',
                'profile_age_hidden',
                'profile_sex_hidden',
                'profile_occupation',
                'profile_about',
                'social_links',
                'notification_sound_prefs',
            ]);
        });
    }
};
