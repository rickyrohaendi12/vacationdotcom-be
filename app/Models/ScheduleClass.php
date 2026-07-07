<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ScheduleClass extends Model
{
    use HasFactory;

    protected $fillable = [
        'schedule_id',
        'transport_class_id',
        'price',
        'seat_count',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
        ];
    }

    public function schedule(): BelongsTo
    {
        return $this->belongsTo(Schedule::class);
    }

    public function transportClass(): BelongsTo
    {
        return $this->belongsTo(TransportClass::class);
    }

    public function seats(): HasMany
    {
        return $this->hasMany(Seat::class);
    }

    public function availableSeatsCount(): int
    {
        return $this->seats()->where('status', 'available')->count();
    }
}
