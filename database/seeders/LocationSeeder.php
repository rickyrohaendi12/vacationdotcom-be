<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class LocationSeeder extends Seeder
{
    public function run(): void
    {
        $locations = [
            // ── Bandara (airport) ──────────────────────────────────────────
            ['type' => 'airport', 'code' => 'CGK', 'name' => 'Soekarno-Hatta International Airport', 'city' => 'Jakarta'],
            ['type' => 'airport', 'code' => 'DPS', 'name' => 'Ngurah Rai International Airport',     'city' => 'Bali'],
            ['type' => 'airport', 'code' => 'SUB', 'name' => 'Juanda International Airport',         'city' => 'Surabaya'],
            ['type' => 'airport', 'code' => 'UPG', 'name' => 'Sultan Hasanuddin International Airport', 'city' => 'Makassar'],
            ['type' => 'airport', 'code' => 'MDC', 'name' => 'Sam Ratulangi International Airport',  'city' => 'Manado'],
            ['type' => 'airport', 'code' => 'BPN', 'name' => 'Sultan Aji Muhammad Sulaiman Airport', 'city' => 'Balikpapan'],
            ['type' => 'airport', 'code' => 'PLM', 'name' => 'Sultan Mahmud Badaruddin II Airport',  'city' => 'Palembang'],
            ['type' => 'airport', 'code' => 'PDG', 'name' => 'Minangkabau International Airport',    'city' => 'Padang'],
            ['type' => 'airport', 'code' => 'LOP', 'name' => 'Lombok International Airport',         'city' => 'Lombok'],
            ['type' => 'airport', 'code' => 'YIA', 'name' => 'Yogyakarta International Airport',     'city' => 'Yogyakarta'],

            // ── Stasiun Kereta (train_station) ────────────────────────────
            ['type' => 'train_station', 'code' => 'GMR', 'name' => 'Stasiun Gambir',        'city' => 'Jakarta'],
            ['type' => 'train_station', 'code' => 'PSE', 'name' => 'Stasiun Pasar Senen',   'city' => 'Jakarta'],
            ['type' => 'train_station', 'code' => 'BD',  'name' => 'Stasiun Bandung',        'city' => 'Bandung'],
            ['type' => 'train_station', 'code' => 'YK',  'name' => 'Stasiun Yogyakarta',     'city' => 'Yogyakarta'],
            ['type' => 'train_station', 'code' => 'SLO', 'name' => 'Stasiun Solo Balapan',   'city' => 'Solo'],
            ['type' => 'train_station', 'code' => 'SMT', 'name' => 'Stasiun Semarang Tawang','city' => 'Semarang'],
            ['type' => 'train_station', 'code' => 'SB',  'name' => 'Stasiun Surabaya Gubeng','city' => 'Surabaya'],
            ['type' => 'train_station', 'code' => 'ML',  'name' => 'Stasiun Malang',         'city' => 'Malang'],
        ];

        foreach ($locations as $loc) {
            DB::table('locations')->insertOrIgnore([
                ...$loc,
                'country'    => 'Indonesia',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}
