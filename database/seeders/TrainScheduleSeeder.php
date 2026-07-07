<?php

namespace Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TrainScheduleSeeder extends Seeder
{
    public function run(): void
    {
        $locations = DB::table('locations')->where('type', 'train_station')->pluck('id', 'code');
        $kaiId     = DB::table('operators')->where('name', 'KAI (Kereta Api Indonesia)')->value('id');
        $classes   = DB::table('transport_classes')->where('operator_id', $kaiId)->get();

        $eksekutif = $classes->firstWhere('name', 'Eksekutif');
        $bisnis    = $classes->firstWhere('name', 'Bisnis');
        $ekonomi   = $classes->firstWhere('name', 'Ekonomi');

        // [nama kereta, kode, asal, tujuan, jam berangkat, jam tiba, harga eksekutif, bisnis, ekonomi]
        $trains = [
            // Jakarta ↔ Yogyakarta
            ['Argo Dwipangga',  'AGD',  'GMR', 'YK',  '08:00', '14:20', 450_000, 300_000, 180_000],
            ['Taksaka',         'TKS',  'GMR', 'YK',  '22:00', '04:20', 430_000, 280_000, 160_000],
            ['Argo Dwipangga',  'AGD2', 'YK',  'GMR', '08:00', '14:20', 450_000, 300_000, 180_000],
            ['Taksaka',         'TKS2', 'YK',  'GMR', '22:00', '04:20', 430_000, 280_000, 160_000],

            // Jakarta ↔ Surabaya
            ['Argo Bromo Anggrek', 'ABA',  'GMR', 'SB', '09:00', '18:00', 650_000, 450_000, null],
            ['Bima',               'BMA',  'GMR', 'SB', '17:00', '03:00', 600_000, 400_000, 220_000],
            ['Argo Bromo Anggrek', 'ABA2', 'SB',  'GMR', '09:00', '18:00', 650_000, 450_000, null],
            ['Bima',               'BMA2', 'SB',  'GMR', '17:00', '03:00', 600_000, 400_000, 220_000],

            // Jakarta ↔ Bandung
            ['Argo Parahyangan', 'ARP',  'GMR', 'BD', '06:00', '09:00', 200_000, 150_000, 80_000],
            ['Argo Parahyangan', 'ARP2', 'GMR', 'BD', '09:00', '12:00', 200_000, 150_000, 80_000],
            ['Argo Parahyangan', 'ARP3', 'GMR', 'BD', '16:00', '19:00', 220_000, 160_000, 90_000],
            ['Argo Parahyangan', 'ARP4', 'BD',  'GMR', '06:00', '09:00', 200_000, 150_000, 80_000],
            ['Argo Parahyangan', 'ARP5', 'BD',  'GMR', '14:00', '17:00', 210_000, 155_000, 85_000],

            // Yogyakarta ↔ Surabaya
            ['Sancaka',  'SCK',  'YK', 'SB', '07:00', '11:30', 280_000, 200_000, 120_000],
            ['Sancaka',  'SCK2', 'YK', 'SB', '16:00', '20:30', 280_000, 200_000, 120_000],
            ['Sancaka',  'SCK3', 'SB', 'YK', '07:00', '11:30', 280_000, 200_000, 120_000],

            // Jakarta ↔ Semarang
            ['Argo Muria', 'AGM',  'GMR', 'SMT', '07:00', '11:00', 350_000, 250_000, null],
            ['Argo Muria', 'AGM2', 'SMT', 'GMR', '07:30', '11:30', 350_000, 250_000, null],
        ];

        $today = Carbon::today();

        foreach ($trains as [$trainName, $code, $originCode, $destCode, $departTime, $arriveTime, $eksPrice, $bisPrice, $ekoPrice]) {
            $originId = $locations[$originCode] ?? null;
            $destId   = $locations[$destCode]   ?? null;
            if (! $originId || ! $destId || ! $kaiId) continue;

            $routeId = DB::table('routes')->insertGetId([
                'type'                    => 'train',
                'operator_id'             => $kaiId,
                'origin_location_id'      => $originId,
                'destination_location_id' => $destId,
                'code'                    => $code,
                'created_at'              => now(),
                'updated_at'              => now(),
            ]);

            for ($day = 0; $day < 30; $day++) {
                $date      = $today->copy()->addDays($day);
                $departure = Carbon::parse($date->toDateString() . ' ' . $departTime);
                $arrival   = Carbon::parse($date->toDateString() . ' ' . $arriveTime);

                if ($arrival->lte($departure)) $arrival->addDay();

                // Weekend surcharge 10%
                $multiplier = $date->isWeekend() ? 1.1 : 1.0;

                $scheduleId = DB::table('schedules')->insertGetId([
                    'route_id'       => $routeId,
                    'departure_time' => $departure,
                    'arrival_time'   => $arrival,
                    'base_price'     => (int) ($eksPrice * $multiplier),
                    'total_seats'    => 400,
                    'created_at'     => now(),
                    'updated_at'     => now(),
                ]);

                // Buat schedule_classes sesuai kelas yang tersedia untuk kereta ini
                $classConfigs = array_filter([
                    $eksekutif ? ['class' => $eksekutif, 'price' => $eksPrice, 'seats' => 50] : null,
                    $bisnis    ? ['class' => $bisnis,    'price' => $bisPrice,  'seats' => 100] : null,
                    ($ekoPrice && $ekonomi) ? ['class' => $ekonomi, 'price' => $ekoPrice, 'seats' => 250] : null,
                ]);

                foreach ($classConfigs as $config) {
                    $finalPrice = (int) ($config['price'] * $multiplier);

                    $scheduleClassId = DB::table('schedule_classes')->insertGetId([
                        'schedule_id'        => $scheduleId,
                        'transport_class_id' => $config['class']->id,
                        'price'              => $finalPrice,
                        'seat_count'         => $config['seats'],
                        'created_at'         => now(),
                        'updated_at'         => now(),
                    ]);

                    // Generate seats kereta (nomor 1-n, kolom A-D)
                    $cols  = ['A', 'B', 'C', 'D'];
                    $rows  = (int) ceil($config['seats'] / 4);
                    $seats = [];
                    $count = 0;

                    for ($row = 1; $row <= $rows && $count < $config['seats']; $row++) {
                        foreach ($cols as $col) {
                            if ($count >= $config['seats']) break;
                            $seats[] = [
                                'schedule_class_id' => $scheduleClassId,
                                'seat_number'       => $row . $col,
                                'status'            => 'available',
                                'locked_until'      => null,
                                'created_at'        => now(),
                                'updated_at'        => now(),
                            ];
                            $count++;
                        }
                    }

                    foreach (array_chunk($seats, 100) as $chunk) {
                        DB::table('seats')->insert($chunk);
                    }
                }
            }
        }
    }
}
