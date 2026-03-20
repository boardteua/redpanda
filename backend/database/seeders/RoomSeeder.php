<?php

namespace Database\Seeders;

use App\Models\Room;
use Illuminate\Database\Seeder;

class RoomSeeder extends Seeder
{
    public function run(): void
    {
        Room::query()->firstOrCreate(
            ['room_name' => 'Загальна'],
            ['topic' => null, 'access' => 0],
        );

        Room::query()->firstOrCreate(
            ['room_name' => 'Для своїх'],
            ['topic' => null, 'access' => 1],
        );
    }
}
