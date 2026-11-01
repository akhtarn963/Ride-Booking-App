<?php

namespace App\Services;

use InvalidArgumentException;

class FareEstimateService
{
    private const VEHICLE_RATES = [
        'economy' => [
            'base_fare' => 150,
            'per_km' => 55,
            'minimum_fare' => 200,
        ],
        'comfort' => [
            'base_fare' => 250,
            'per_km' => 75,
            'minimum_fare' => 350,
        ],
    ];

    public function estimate(
        float $pickupLat,
        float $pickupLng,
        float $destinationLat,
        float $destinationLng,
        string $category
    ): array {
        if (!isset(self::VEHICLE_RATES[$category])) {
            throw new InvalidArgumentException('Invalid vehicle category.');
        }

        $distance = $this->calculateDistance(
            $pickupLat,
            $pickupLng,
            $destinationLat,
            $destinationLng
        );

        // Approximate road distance using a 1.25 multiplier.
        $distance = $distance * 1.25;

        $rates = self::VEHICLE_RATES[$category];

        $fare = max(
            $rates['minimum_fare'],
            $rates['base_fare'] + ($distance * $rates['per_km'])
        );

        // Assume an average city speed of 25 km/h, plus 5 minutes.
        $duration = ($distance / 25) * 60 + 5;

        return [
            'distance_km' => round($distance, 2),
            'estimated_fare' => round($fare, 2),
            'estimated_duration_minutes' => (int) ceil($duration),
            'currency' => 'PKR',
            'vehicle_category' => $category,
        ];
    }

    private function calculateDistance(
        float $lat1,
        float $lon1,
        float $lat2,
        float $lon2
    ): float {
        $earthRadius = 6371;

        $latDifference = deg2rad($lat2 - $lat1);
        $lonDifference = deg2rad($lon2 - $lon1);

        $a = sin($latDifference / 2) ** 2
            + cos(deg2rad($lat1))
            * cos(deg2rad($lat2))
            * sin($lonDifference / 2) ** 2;

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $earthRadius * $c;
    }
}