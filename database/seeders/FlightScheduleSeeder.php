<?php

namespace Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class FlightScheduleSeeder extends Seeder
{
    public function run(): void
    {
        // Ambil data lokasi dan operator dari DB
        $locations  = DB::table('locations')->where('type', 'airport')->pluck('id', 'code');
        $operators  = DB::table('operators')->where('type', 'airline')->pluck('id', 'name');
        $tcAll      = DB::table('transport_classes')->get()->groupBy('operator_id');

        // Definisi rute populer
        $routes = [
            // [maskapai, kode penerbangan, asal, tujuan, jam berangkat, jam tiba, base_price]
            ['Garuda Indonesia', 'GA-401',  'CGK', 'DPS', '06:00', '08:15', 1_200_000],
            ['Garuda Indonesia', 'GA-403',  'CGK', 'DPS', '09:00', '11:15', 1_350_000],
            ['Garuda Indonesia', 'GA-405',  'CGK', 'DPS', '14:00', '16:15', 1_100_000],
            ['Garuda Indonesia', 'GA-407',  'CGK', 'DPS', '18:30', '20:45', 1_250_000],
            ['Garuda Indonesia', 'GA-450',  'DPS', 'CGK', '07:00', '09:15', 1_200_000],
            ['Garuda Indonesia', 'GA-452',  'DPS', 'CGK', '15:00', '17:15', 1_150_000],

            ['Lion Air',        'JT-010',  'CGK', 'SUB', '07:30', '09:00', 650_000],
            ['Lion Air',        'JT-012',  'CGK', 'SUB', '11:00', '12:30', 700_000],
            ['Lion Air',        'JT-014',  'CGK', 'SUB', '16:00', '17:30', 680_000],
            ['Lion Air',        'JT-050',  'SUB', 'CGK', '08:00', '09:30', 650_000],
            ['Lion Air',        'JT-052',  'SUB', 'CGK', '13:00', '14:30', 700_000],

            ['Batik Air',       'ID-6380', 'CGK', 'UPG', '06:30', '09:00', 900_000],
            ['Batik Air',       'ID-6382', 'CGK', 'UPG', '13:00', '15:30', 950_000],
            ['Batik Air',       'ID-6384', 'UPG', 'CGK', '10:00', '12:30', 920_000],

            ['Citilink',        'QG-830',  'CGK', 'YIA', '07:00', '08:10', 450_000],
            ['Citilink',        'QG-832',  'CGK', 'YIA', '10:00', '11:10', 500_000],
            ['Citilink',        'QG-834',  'CGK', 'YIA', '15:00', '16:10', 480_000],
            ['Citilink',        'QG-836',  'YIA', 'CGK', '09:00', '10:10', 450_000],

            ['AirAsia',         'AK-300',  'CGK', 'DPS', '08:00', '10:10', 550_000],
            ['AirAsia',         'AK-302',  'CGK', 'DPS', '20:00', '22:10', 480_000],
            ['AirAsia',         'AK-350',  'DPS', 'CGK', '11:00', '13:10', 550_000],

            ['Garuda Indonesia', 'GA-600', 'CGK', 'BPN', '07:00', '09:30', 1_100_000],
            ['Lion Air',        'JT-200',  'CGK', 'PLM', '09:00', '10:30', 550_000],
            ['Citilink',        'QG-900',  'CGK', 'LOP', '10:00', '11:40', 700_000],
        ];

        // Buat jadwal untuk 30 hari ke depan
        $today = Carbon::today();

        foreach ($routes as [$airlineName, $flightCode, $originCode, $destCode, $departTime, $arriveTime, $basePrice]) {
            $operatorId = $operators[$airlineName] ?? null;
            $originId   = $locations[$originCode] ?? null;
            $destId     = $locations[$destCode] ?? null;

            if (! $operatorId || ! $originId || ! $destId) continue;

            // Buat route
            $routeId = DB::table('routes')->insertGetId([
                'type'                    => 'flight',
                'operator_id'             => $operatorId,
                'origin_location_id'      => $originId,
                'destination_location_id' => $destId,
                'code'                    => $flightCode,
                'created_at'              => now(),
                'updated_at'              => now(),
            ]);

            // Kelas yang tersedia untuk operator ini
            $classes = $tcAll[$operatorId] ?? collect();

            // Buat jadwal untuk setiap hari (30 hari ke depan)
            for ($day = 0; $day < 30; $day++) {
                $date = $today->copy()->addDays($day);

                // Skip tanggal tertentu secara acak (simulasi penerbangan tidak setiap hari)
                // Lion Air dan Citilink terbang setiap hari, Garuda 6x/minggu
                if ($airlineName === 'Garuda Indonesia' && $date->isSunday() && rand(0, 1)) continue;

                $departure = Carbon::parse($date->toDateString() . ' ' . $departTime);
                $arrival   = Carbon::parse($date->toDateString() . ' ' . $arriveTime);

                // Kalau arrival lebih awal dari departure (lintas tengah malam)
                if ($arrival->lt($departure)) $arrival->addDay();

                // Variasi harga ±15% berdasarkan hari (weekend lebih mahal)
                $priceMultiplier = $date->isWeekend() ? 1.15 : 1.0;
                $finalBasePrice  = (int) ($basePrice * $priceMultiplier);

                $scheduleId = DB::table('schedules')->insertGetId([
                    'route_id'       => $routeId,
                    'departure_time' => $departure,
                    'arrival_time'   => $arrival,
                    'base_price'     => $finalBasePrice,
                    'total_seats'    => 180,
                    'created_at'     => now(),
                    'updated_at'     => now(),
                ]);

                // Buat schedule_classes dan seats
                foreach ($classes as $class) {
                    $isEconomy  = strtolower($class->name) === 'economy';
                    $seatCount  = $isEconomy ? 150 : 30;
                    $classPrice = $isEconomy ? $finalBasePrice : (int) ($finalBasePrice * 3.5);

                    $scheduleClassId = DB::table('schedule_classes')->insertGetId([
                        'schedule_id'        => $scheduleId,
                        'transport_class_id' => $class->id,
                        'price'              => $classPrice,
                        'seat_count'         => $seatCount,
                        'created_at'         => now(),
                        'updated_at'         => now(),
                    ]);

                    // Generate seats (12A, 12B, dst.)
                    $rows    = (int) ceil($seatCount / 6);
                    $columns = ['A', 'B', 'C', 'D', 'E', 'F'];
                    $seats   = [];
                    $count   = 0;

                    for ($row = 1; $row <= $rows && $count < $seatCount; $row++) {
                        foreach ($columns as $col) {
                            if ($count >= $seatCount) break;
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

                    // Insert dalam batch biar efisien
                    foreach (array_chunk($seats, 100) as $chunk) {
                        DB::table('seats')->insert($chunk);
                    }
                }
            }
        }
    }
}
