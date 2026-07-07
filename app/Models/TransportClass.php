<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TransportClass extends Model
{
    use HasFactory;

    protected $fillable = [
        'operator_id',
        'name',
        'facilities',
    ];

    protected function casts(): array
    {
        return [
            'facilities' => 'array',
        ];
    }

    public function operator(): BelongsTo
    {
        return $this->belongsTo(Operator::class);
    }

    public function scheduleClasses(): HasMany
    {
        return $this->hasMany(ScheduleClass::class);
    }
}
