<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('profile_occupation_hidden')->default(false)->after('profile_about');
            $table->boolean('profile_about_hidden')->default(false)->after('profile_occupation_hidden');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['profile_occupation_hidden', 'profile_about_hidden']);
        });
    }
};
