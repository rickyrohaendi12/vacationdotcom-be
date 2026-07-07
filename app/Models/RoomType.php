<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RoomType extends Model
{
    use HasFactory;

    protected $fillable = [
        'hotel_id',
        'name',
        'price_per_night',
        'capacity',
        'total_rooms',
    ];

    protected function casts(): array
    {
        return [
            'price_per_night' => 'decimal:2',
        ];
    }

    public function hotel(): BelongsTo
    {
        return $this->belongsTo(Hotel::class);
    }

    public function availabilities(): HasMany
    {
        return $this->hasMany(RoomAvailability::class);
    }

    /**
     * Cek apakah kamar tersedia untuk setiap tanggal dalam range checkin-checkout.
     * Kalau ada satu tanggal saja yang available_count nya 0, dianggap tidak tersedia.
     */
    public function isAvailableBetween(string $checkIn, string $checkOut): bool
    {
        $dates = collect();
        $current = \Carbon\Carbon::parse($checkIn);
        $end = \Carbon\Carbon::parse($checkOut);

        while ($current->lt($end)) {
            $dates->push($current->toDateString());
            $current->addDay();
        }

        $availableDatesCount = $this->availabilities()
            ->whereIn('date', $dates)
            ->where('available_count', '>', 0)
            ->count();

        return $availableDatesCount === $dates->count();
    }
}
