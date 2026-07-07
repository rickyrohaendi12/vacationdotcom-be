<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\CreateBookingRequest;
use App\Models\Booking;
use App\Models\BookingItem;
use App\Models\Passenger;
use App\Models\ScheduleClass;
use App\Models\Seat;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BookingController extends Controller
{
    /**
     * Buat booking baru.
     * Logic:
     * 1. Lock semua kursi yang dipilih di dalam DB transaction
     * 2. Kalau ada kursi yang gagal di-lock (sudah diambil orang lain), rollback
     * 3. Kalau semua berhasil, buat booking + booking_items + passengers
     * 4. Booking expires dalam 15 menit (user harus bayar sebelum itu)
     */
    public function store(CreateBookingRequest $request): JsonResponse
    {
        $user            = $request->user();
        $scheduleClassId = $request->schedule_class_id;
        $seatIds         = $request->seat_ids;
        $passengersData  = $request->passengers;

        try {
            $booking = DB::transaction(function () use ($user, $scheduleClassId, $seatIds, $passengersData) {
                $scheduleClass = ScheduleClass::findOrFail($scheduleClassId);

                // ── Step 1: Lock semua kursi yang dipilih ──────────────────
                $failedSeats = [];
                foreach ($seatIds as $seatId) {
                    $seat = Seat::findOrFail($seatId);
                    if (! $seat->tryLock(15)) { // lock 15 menit
                        $failedSeats[] = $seat->seat_number;
                    }
                }

                // Kalau ada kursi yang gagal di-lock, batalkan semua
                if (! empty($failedSeats)) {
                    // Lepas lock yang sudah berhasil
                    Seat::whereIn('id', $seatIds)
                        ->where('status', 'locked')
                        ->whereNotNull('locked_until')
                        ->get()
                        ->each(fn($s) => $s->releaseLock());

                    throw new \RuntimeException(
                        'Kursi ' . implode(', ', $failedSeats) . ' sudah tidak tersedia. Silakan pilih kursi lain.'
                    );
                }

                // ── Step 2: Hitung total harga ─────────────────────────────
                $pricePerSeat = (float) $scheduleClass->price;
                $totalAmount  = $pricePerSeat * count($seatIds);

                // ── Step 3: Buat booking header ────────────────────────────
                $booking = Booking::create([
                    'user_id'      => $user->id,
                    'status'       => 'pending',
                    'total_amount' => $totalAmount,
                    'expires_at'   => now()->addMinutes(15),
                    // booking_code di-generate otomatis di model (booted method)
                ]);

                // ── Step 4: Buat booking item ──────────────────────────────
                $routeType = $scheduleClass->schedule->route->type; // 'flight' atau 'train'

                $bookingItem = BookingItem::create([
                    'booking_id'    => $booking->id,
                    'itemable_type' => $routeType,
                    'itemable_id'   => $scheduleClassId,
                    'price'         => $totalAmount,
                    'details'       => [
                        'schedule_class_id' => $scheduleClassId,
                        'class_name'        => $scheduleClass->transportClass->name,
                    ],
                ]);

                // ── Step 5: Buat data penumpang ────────────────────────────
                foreach ($passengersData as $pData) {
                    Passenger::create([
                        'booking_item_id' => $bookingItem->id,
                        'name'            => $pData['name'],
                        'id_number'       => $pData['id_number'],
                        'seat_id'         => $pData['seat_id'],
                    ]);
                }

                return $booking;
            });

            return response()->json([
                'message'      => 'Booking berhasil dibuat.',
                'booking_code' => $booking->booking_code,
                'total_amount' => $booking->total_amount,
                'expires_at'   => $booking->expires_at->toIso8601String(),
            ], 201);

        } catch (\RuntimeException $e) {
            // Error seat locking (kursi sudah diambil)
            return response()->json(['message' => $e->getMessage()], 409);
        } catch (\Throwable $e) {
            return response()->json(['message' => 'Terjadi kesalahan. Silakan coba lagi.'], 500);
        }
    }

    /**
     * Ambil daftar booking milik user yang sedang login.
     *
     * GET /api/bookings
     */
    public function index(Request $request): JsonResponse
    {
        $bookings = Booking::with(['items.passengers'])
            ->where('user_id', $request->user()->id)
            ->latest()
            ->get()
            ->map(fn($booking) => [
                'id'           => $booking->id,
                'booking_code' => $booking->booking_code,
                'status'       => $booking->status,
                'total_amount' => (float) $booking->total_amount,
                'expires_at'   => $booking->expires_at->toIso8601String(),
                'created_at'   => $booking->created_at->toIso8601String(),
                'items_count'  => $booking->items->count(),
            ]);

        return response()->json(['data' => $bookings]);
    }

    /**
     * Detail booking tertentu milik user.
     *
     * GET /api/bookings/{booking_code}
     */
    public function show(Request $request, string $bookingCode): JsonResponse
    {
        $booking = Booking::with([
            'items.passengers.seat',
        ])
        ->where('user_id', $request->user()->id)
        ->where('booking_code', $bookingCode)
        ->firstOrFail();

        return response()->json([
            'data' => [
                'id'           => $booking->id,
                'booking_code' => $booking->booking_code,
                'status'       => $booking->status,
                'total_amount' => (float) $booking->total_amount,
                'expires_at'   => $booking->expires_at->toIso8601String(),
                'created_at'   => $booking->created_at->toIso8601String(),
                'items'        => $booking->items->map(fn($item) => [
                    'id'         => $item->id,
                    'type'       => $item->itemable_type,
                    'price'      => (float) $item->price,
                    'details'    => $item->details,
                    'passengers' => $item->passengers->map(fn($p) => [
                        'name'        => $p->name,
                        'id_number'   => $p->id_number,
                        'seat_number' => $p->seat?->seat_number,
                    ]),
                ]),
            ],
        ]);
    }
}
