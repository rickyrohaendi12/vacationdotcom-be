<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            LocationSeeder::class,        // 1. Lokasi dulu (bandara & stasiun)
            OperatorSeeder::class,        // 2. Operator + kelas kursi
            FlightScheduleSeeder::class,  // 3. Rute & jadwal pesawat (30 hari)
            TrainScheduleSeeder::class,   // 4. Rute & jadwal kereta (30 hari)
        ]);
    }
}
