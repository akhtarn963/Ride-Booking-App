<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Ride extends Model
{
    public const STATUS_REQUESTED = 'requested';
    public const STATUS_ACCEPTED = 'accepted';
    public const STATUS_IN_PROGRESS = 'in_progress';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_CANCELLED = 'cancelled';

    public const CATEGORY_ECONOMY = 'economy';
    public const CATEGORY_COMFORT = 'comfort';

    protected $fillable = [
        'rider_id',
        'pickup_location',
        'pickup_latitude',
        'pickup_longitude',
        'destination_location',
        'destination_latitude',
        'destination_longitude',
        'vehicle_category',
        'estimated_fare',
        'estimated_duration_minutes',
        'final_fare',
        'status',
        'requested_at',
        'accepted_at',
        'started_at',
        'completed_at',
        'cancelled_at',
    ];

    protected function casts(): array
    {
        return [
            'estimated_fare' => 'decimal:2',
            'final_fare' => 'decimal:2',
            'requested_at' => 'datetime',
            'accepted_at' => 'datetime',
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
            'cancelled_at' => 'datetime',
        ];
    }

    public function rider(): BelongsTo
    {
        return $this->belongsTo(Rider::class);
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(RideAssignment::class);
    }

    public function activeAssignment()
    {
        return $this->hasOne(RideAssignment::class)
            ->whereNull('released_at');
    }

    public function idempotencyKeys() : HasMany
    {
        return $this->hasMany(IdempotencyKey::class);
    }
}