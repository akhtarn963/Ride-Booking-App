<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;

class Driver extends Authenticatable
{
    use HasApiTokens, HasFactory;

    protected $fillable = [
        'name',
        'phone',
        'vehicle_category',
        'vehicle_model',
        'vehicle_number',
        'is_available',
        'latitude',
        'longitude',
    ];

    protected function casts(): array
    {
        return [
            'is_available' => 'boolean',
            'latitude' => 'decimal:7',
            'longitude' => 'decimal:7',
        ];
    }

    public function rideAssignments(): HasMany
    {
        return $this->hasMany(RideAssignment::class);
    }
}