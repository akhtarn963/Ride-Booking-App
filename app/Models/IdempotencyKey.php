<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class IdempotencyKey extends Model
{
    protected $fillable = [
        'rider_id',
        'key',
        'request_hash',
        'ride_id',
    ];
}