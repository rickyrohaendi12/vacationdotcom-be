<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Location extends Model
{
    use HasFactory;

    protected $fillable = [
        'type',
        'code',
        'name',
        'city',
        'country',
    ];

    public function originRoutes(): HasMany
    {
        return $this->hasMany(Route::class, 'origin_location_id');
    }

    public function destinationRoutes(): HasMany
    {
        return $this->hasMany(Route::class, 'destination_location_id');
    }
}
