<?php

namespace App\Services;

use App\Models\Driver;
use App\Models\Ride;
use App\Models\RideAssignment;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class DriverMatchingService
{
    public function assignDriver(Ride $ride): ?RideAssignment
    {
        return DB::transaction(function () use ($ride) {

            // Lock the ride so it cannot be assigned twice.
            $ride = Ride::whereKey($ride->id)
                ->lockForUpdate()
                ->firstOrFail();

            // Do not assign a driver to a ride that is not requested.
            if ($ride->status !== Ride::STATUS_REQUESTED) {
                return null;
            }

            // Return the existing assignment if one already exists.
            $existingAssignment = RideAssignment::where('ride_id', $ride->id)
                ->whereNull('released_at')
                ->first();

            if ($existingAssignment) {
                return $existingAssignment;
            }

            // Find and lock one available driver in the required category.
            $driver = Driver::where('vehicle_category', $ride->vehicle_category)
                ->where('is_available', true)
                ->orderBy('id')
                ->lock('FOR UPDATE SKIP LOCKED')
                ->first();

            // No matching driver is currently available.
            if (!$driver) {
                return null;
            }

            // Create the active assignment.
            $assignment = RideAssignment::create([
                'ride_id' => $ride->id,
                'driver_id' => $driver->id,
                'assigned_at' => now(),
            ]);

            // Mark the driver unavailable.
            $driver->update([
                'is_available' => false,
            ]);

            return $assignment->load('driver', 'ride');
        }, 3);
    }
}
