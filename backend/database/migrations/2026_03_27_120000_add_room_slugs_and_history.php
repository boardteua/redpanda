<?php

use App\Services\Chat\RoomSlugService;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('rooms', function (Blueprint $table): void {
            $table->string('slug', 191)->nullable()->unique();
        });

        Schema::create('room_slug_histories', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('room_id');
            $table->string('slug', 191);
            $table->timestamps();

            $table->unique('slug');
            $table->index('room_id');
            $table->foreign('room_id')->references('room_id')->on('rooms')->cascadeOnDelete();
        });

        $service = app(RoomSlugService::class);

        foreach (DB::table('rooms')->orderBy('room_id')->cursor() as $row) {
            $slug = $service->proposeUniqueSlugFromName((string) $row->room_name, (int) $row->room_id);
            DB::table('rooms')->where('room_id', $row->room_id)->update(['slug' => $slug]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('room_slug_histories');

        Schema::table('rooms', function (Blueprint $table): void {
            $table->dropUnique(['slug']);
            $table->dropColumn('slug');
        });
    }
};
