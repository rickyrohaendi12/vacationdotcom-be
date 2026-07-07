<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class OperatorSeeder extends Seeder
{
    public function run(): void
    {
        // ── Maskapai ───────────────────────────────────────────────────────
        $airlines = [
            ['name' => 'Garuda Indonesia', 'logo' => 'operators/garuda.png'],
            ['name' => 'Lion Air',         'logo' => 'operators/lion.png'],
            ['name' => 'Batik Air',        'logo' => 'operators/batik.png'],
            ['name' => 'Citilink',         'logo' => 'operators/citilink.png'],
            ['name' => 'AirAsia',          'logo' => 'operators/airasia.png'],
        ];

        foreach ($airlines as $airline) {
            $operatorId = DB::table('operators')->insertGetId([
                'type'       => 'airline',
                'name'       => $airline['name'],
                'logo'       => $airline['logo'],
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Kelas untuk maskapai
            $classes = match ($airline['name']) {
                'Garuda Indonesia' => [
                    ['name' => 'Economy',  'facilities' => ['Bagasi 23kg', 'Snack', 'Hiburan']],
                    ['name' => 'Business', 'facilities' => ['Bagasi 40kg', 'Makan Besar', 'Lounge Akses', 'Kursi Lie-flat']],
                ],
                'Batik Air' => [
                    ['name' => 'Economy',  'facilities' => ['Bagasi 20kg', 'Snack']],
                    ['name' => 'Business', 'facilities' => ['Bagasi 30kg', 'Makan Besar', 'Kursi Ekstra Lebar']],
                ],
                default => [
                    ['name' => 'Economy', 'facilities' => ['Bagasi 20kg']],
                ],
            };

            foreach ($classes as $class) {
                DB::table('transport_classes')->insert([
                    'operator_id' => $operatorId,
                    'name'        => $class['name'],
                    'facilities'  => json_encode($class['facilities']),
                    'created_at'  => now(),
                    'updated_at'  => now(),
                ]);
            }
        }

        // ── Operator Kereta ────────────────────────────────────────────────
        $trainOperatorId = DB::table('operators')->insertGetId([
            'type'       => 'train',
            'name'       => 'KAI (Kereta Api Indonesia)',
            'logo'       => 'operators/kai.png',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $trainClasses = [
            ['name' => 'Eksekutif', 'facilities' => ['AC', 'Reclining Seat', 'Colokan Listrik', 'Selimut']],
            ['name' => 'Bisnis',    'facilities' => ['AC', 'Reclining Seat', 'Colokan Listrik']],
            ['name' => 'Ekonomi',   'facilities' => ['AC', 'Kursi Tegak']],
        ];

        foreach ($trainClasses as $class) {
            DB::table('transport_classes')->insert([
                'operator_id' => $trainOperatorId,
                'name'        => $class['name'],
                'facilities'  => json_encode($class['facilities']),
                'created_at'  => now(),
                'updated_at'  => now(),
            ]);
        }
    }
}
