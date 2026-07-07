<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ScheduleClass;
use Illuminate\Http\JsonResponse;

class ScheduleClassController extends Controller
{
    /**
     * Ambil detail schedule class beserta seat map.
     * Dipakai halaman booking untuk tampilkan kursi yang available/locked/booked.
     *
     * GET /api/schedule-classes/{id}
     */
    public function show(int $id): JsonResponse
    {
        $scheduleClass = ScheduleClass::with([
            'schedule.route.operator',
            'schedule.route.origin',
            'schedule.route.destination',
            'transportClass',
            'seats' => fn($q) => $q->orderBy('seat_number'),
        ])->findOrFail($id);

        $schedule = $scheduleClass->schedule;
        $route    = $schedule->route;

        return response()->json([
            'data' => [
                'schedule_class_id' => $scheduleClass->id,
                'class_name'        => $scheduleClass->transportClass->name,
                'facilities'        => $scheduleClass->transportClass->facilities ?? [],
                'price'             => (float) $scheduleClass->price,
                'flight_code'       => $route->code,
                'operator'          => [
                    'name' => $route->operator->name,
                    'logo' => $route->operator->logo,
                ],
                'origin'            => [
                    'code' => $route->origin->code,
                    'city' => $route->origin->city,
                ],
                'destination'       => [
                    'code' => $route->destination->code,
                    'city' => $route->destination->city,
                ],
                'departure_time'    => $schedule->departure_time->toIso8601String(),
                'arrival_time'      => $schedule->arrival_time->toIso8601String(),
                'seats'             => $scheduleClass->seats->map(fn($seat) => [
                    'id'          => $seat->id,
                    'seat_number' => $seat->seat_number,
                    'status'      => $this->resolveStatus($seat),
                ]),
            ],
        ]);
    }

    /**
     * Kalau kursi statusnya 'locked' tapi locked_until sudah lewat,
     * anggap kursi tersebut available lagi (tanpa perlu update DB di sini).
     */
    private function resolveStatus($seat): string
    {
        if ($seat->status === 'locked' && $seat->locked_until?->isPast()) {
            return 'available';
        }
        return $seat->status;
    }
}
