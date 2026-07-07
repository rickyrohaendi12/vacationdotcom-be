<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Payment;
use App\Models\Seat;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Midtrans\Config;
use Midtrans\Snap;
use Midtrans\Notification;

class PaymentController extends Controller
{
    public function __construct()
    {
        Config::$serverKey    = config('midtrans.server_key');
        Config::$isProduction = config('midtrans.is_production');
        Config::$isSanitized  = true;
        Config::$is3ds        = true;
    }

    /**
     * Generate Snap token untuk halaman payment.
     * Dipanggil React saat halaman /payment dibuka.
     *
     * POST /api/payments/token
     */
    public function getToken(Request $request): JsonResponse
    {
        $request->validate([
            'booking_code' => ['required', 'string'],
        ]);

        $booking = Booking::with(['user', 'items.passengers'])
            ->where('user_id', $request->user()->id)
            ->where('booking_code', $request->booking_code)
            ->firstOrFail();

        // Cek apakah booking masih valid untuk dibayar
        if ($booking->status !== 'pending') {
            return response()->json([
                'message' => 'Booking ini sudah ' . $booking->status . '.',
            ], 422);
        }

        if ($booking->isExpired()) {
            $booking->update(['status' => 'expired']);
            return response()->json([
                'message' => 'Waktu pembayaran sudah habis. Silakan buat booking baru.',
            ], 422);
        }

        // Buat atau ambil payment record
        $payment = Payment::firstOrCreate(
            ['booking_id' => $booking->id],
            [
                'method' => 'midtrans',
                'status' => 'pending',
                'amount' => $booking->total_amount,
            ]
        );

        // Parameter untuk Midtrans Snap
        $params = [
            'transaction_details' => [
                'order_id'     => $booking->booking_code . '-' . time(),
                'gross_amount' => (int) $booking->total_amount,
            ],
            'customer_details' => [
                'first_name' => $booking->user->name,
                'email'      => $booking->user->email,
                'phone'      => $booking->user->phone ?? '',
            ],
            'item_details' => [
                [
                    'id'       => $booking->booking_code,
                    'price'    => (int) $booking->total_amount,
                    'quantity' => 1,
                    'name'     => 'Booking ' . $booking->booking_code,
                ]
            ],
            'expiry' => [
                'start_time' => now()->format('Y-m-d H:i:s O'),
                'unit'       => 'minutes',
                'duration'   => 15,
            ],
        ];

        try {
            $snapToken = Snap::getSnapToken($params);

            return response()->json([
                'snap_token'   => $snapToken,
                'booking_code' => $booking->booking_code,
                'total_amount' => (float) $booking->total_amount,
                'expires_at'   => $booking->expires_at->toIso8601String(),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Gagal membuat token pembayaran: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Webhook dari Midtrans — dipanggil otomatis saat status pembayaran berubah.
     * Endpoint ini PUBLIC (tidak perlu token) karena dipanggil server Midtrans.
     *
     * POST /api/payments/webhook
     */
    public function webhook(Request $request): JsonResponse
    {
        try {
            $notification = new Notification();

            $orderId           = $notification->order_id;
            $transactionStatus = $notification->transaction_status;
            $fraudStatus       = $notification->fraud_status;
            $transactionId     = $notification->transaction_id;

            $booking = Booking::with(['items.passengers.seat'])
                ->where('booking_code', $orderId)
                ->first();

            if (! $booking) {
                return response()->json(['message' => 'Booking tidak ditemukan.'], 404);
            }

            $payment = Payment::where('booking_id', $booking->id)->first();

            // Tentukan status berdasarkan response Midtrans
            if ($transactionStatus === 'capture') {
                $status = $fraudStatus === 'accept' ? 'success' : 'failed';
            } elseif ($transactionStatus === 'settlement') {
                $status = 'success';
            } elseif (in_array($transactionStatus, ['cancel', 'deny', 'expire'])) {
                $status = 'failed';
            } else {
                $status = 'pending';
            }

            // Update payment
            if ($payment) {
                $payment->update([
                    'status'          => $status,
                    'transaction_ref' => $transactionId,
                    'paid_at'         => $status === 'success' ? now() : null,
                ]);
            }

            // Update booking & kursi
            if ($status === 'success') {
                $booking->update(['status' => 'paid']);

                // Ubah status kursi dari locked → booked
                $booking->items->each(function ($item) {
                    $item->passengers->each(function ($passenger) {
                        $passenger->seat?->markAsBooked();
                    });
                });
            } elseif ($status === 'failed') {
                $booking->update(['status' => 'cancelled']);

                // Lepas lock kursi supaya bisa dipesan orang lain
                $booking->items->each(function ($item) {
                    $item->passengers->each(function ($passenger) {
                        $passenger->seat?->releaseLock();
                    });
                });
            }

            return response()->json(['message' => 'OK']);

        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    /**
     * Cek status booking setelah pembayaran.
     * Dipanggil React setelah popup Midtrans ditutup.
     *
     * GET /api/payments/status/{booking_code}
     */
    public function status(Request $request, string $bookingCode): JsonResponse
    {
        $booking = Booking::with('payment')
            ->where('user_id', $request->user()->id)
            ->where('booking_code', $bookingCode)
            ->firstOrFail();

        return response()->json([
            'booking_code' => $booking->booking_code,
            'status'       => $booking->status,
            'payment'      => $booking->payment ? [
                'status'  => $booking->payment->status,
                'paid_at' => $booking->payment->paid_at?->toIso8601String(),
            ] : null,
        ]);
    }
}
