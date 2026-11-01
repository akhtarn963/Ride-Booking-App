<?php

namespace App\Services;

use App\Models\IdempotencyKey;
use App\Models\Ride;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RideBookingService
{
    public function __construct(
        private FareEstimateService $fareService
    ) {}

    public function createRide(array $data, string $idempotencyKey): array
    {
        $requestHash = hash('sha256', json_encode($data, JSON_THROW_ON_ERROR));

        return DB::transaction(function () use ($data, $idempotencyKey, $requestHash) {
            DB::table('idempotency_keys')->insertOrIgnore([
                'rider_id' => $data['rider_id'],
                'key' => $idempotencyKey,
                'request_hash' => $requestHash,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $keyRecord = IdempotencyKey::where('rider_id', $data['rider_id'])
                ->where('key', $idempotencyKey)
                ->lockForUpdate()
                ->firstOrFail();

            if (!hash_equals($keyRecord->request_hash, $requestHash)) {
                throw ValidationException::withMessages([
                    'idempotency_key' => [
                        'This idempotency key has already been used with different request data.',
                    ],
                ]);
            }

            if ($keyRecord->ride_id) {
                return [
                    'ride' => Ride::with('rider', 'activeAssignment.driver')
                        ->findOrFail($keyRecord->ride_id),
                    'created' => false,
                ];
            }

            $estimate = $this->fareService->estimate(
                (float) $data['pickup_latitude'],
                (float) $data['pickup_longitude'],
                (float) $data['destination_latitude'],
                (float) $data['destination_longitude'],
                $data['vehicle_category']
            );

            $ride = Ride::create([
                'rider_id' => $data['rider_id'],
                'pickup_location' => $data['pickup_location'],
                'pickup_latitude' => $data['pickup_latitude'],
                'pickup_longitude' => $data['pickup_longitude'],
                'destination_location' => $data['destination_location'],
                'destination_latitude' => $data['destination_latitude'],
                'destination_longitude' => $data['destination_longitude'],
                'vehicle_category' => $data['vehicle_category'],
                'estimated_fare' => $estimate['estimated_fare'],
                'estimated_duration_minutes' => $estimate['estimated_duration_minutes'],
                'status' => Ride::STATUS_REQUESTED,
                'requested_at' => now(),
            ]);

            $keyRecord->update(['ride_id' => $ride->id]);

            return [
                'ride' => $ride->load('rider'),
                'created' => true,
            ];
        }, 3);
    }
}