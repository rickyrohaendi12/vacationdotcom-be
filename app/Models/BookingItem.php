<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BookingItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'booking_id',
        'itemable_type',
        'itemable_id',
        'price',
        'details',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'details' => 'array',
        ];
    }

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    public function passengers(): HasMany
    {
        return $this->hasMany(Passenger::class);
    }

    /**
     * Karena itemable_type cuma enum string ('flight','train','hotel') bukan
     * morphTo bawaan Eloquent, kita resolve manual relasinya di sini.
     * Ini sengaja dibuat simpel (bukan polymorphic standar Laravel) supaya
     * lebih mudah dibaca untuk skala project portfolio.
     */
    public function getItemableAttribute(): ?Model
    {
        return match ($this->itemable_type) {
            'flight', 'train' => Seat::find($this->itemable_id),
            'hotel' => RoomType::find($this->itemable_id),
            default => null,
        };
    }
}
