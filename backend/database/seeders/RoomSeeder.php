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
            ['topic' => null, 'access' => Room::ACCESS_REGISTERED],
        );

        Room::query()->firstOrCreate(
            ['room_name' => 'VIP-зал'],
            ['topic' => null, 'access' => Room::ACCESS_VIP],
        );
    }
}
