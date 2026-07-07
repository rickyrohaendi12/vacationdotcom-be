<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Seat extends Model
{
    use HasFactory;

    protected $fillable = [
        'schedule_class_id',
        'seat_number',
        'status',
        'locked_until',
    ];

    protected function casts(): array
    {
        return [
            'locked_until' => 'datetime',
        ];
    }

    public function scheduleClass(): BelongsTo
    {
        return $this->belongsTo(ScheduleClass::class);
    }

    public function passenger(): HasOne
    {
        return $this->hasOne(Passenger::class);
    }

    /**
     * Coba lock kursi ini untuk sementara (default 10 menit) supaya tidak
     * di-booking user lain saat sedang checkout. Pakai database lock untuk
     * mencegah race condition ketika 2 user klik kursi yang sama bersamaan.
     *
     * Dipanggil di dalam DB::transaction() oleh service booking.
     */
    public function tryLock(int $minutes = 10): bool
    {
        // lockForUpdate mengunci baris ini di level database sampai
        // transaction selesai, jadi request lain yang coba lock kursi
        // yang sama akan menunggu (bukan langsung baca data lama).
        $fresh = self::lockForUpdate()->find($this->id);

        $isAvailable = $fresh->status === 'available';
        $isExpiredLock = $fresh->status === 'locked'
            && $fresh->locked_until !== null
            && $fresh->locked_until->isPast();

        if (! $isAvailable && ! $isExpiredLock) {
            return false; // sudah di-lock orang lain atau sudah booked
        }

        $fresh->update([
            'status' => 'locked',
            'locked_until' => Carbon::now()->addMinutes($minutes),
        ]);

        return true;
    }

    public function markAsBooked(): void
    {
        $this->update([
            'status' => 'booked',
            'locked_until' => null,
        ]);
    }

    public function releaseLock(): void
    {
        $this->update([
            'status' => 'available',
            'locked_until' => null,
        ]);
    }
}
