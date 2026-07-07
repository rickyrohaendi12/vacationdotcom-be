<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Operator extends Model
{
    use HasFactory;

    protected $fillable = [
        'type',
        'name',
        'logo',
    ];

    public function transportClasses(): HasMany
    {
        return $this->hasMany(TransportClass::class);
    }

    public function routes(): HasMany
    {
        return $this->hasMany(Route::class);
    }
}
