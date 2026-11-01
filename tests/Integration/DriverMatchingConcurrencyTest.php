<?php

namespace Tests\Integration;

use App\Models\Driver;
use App\Models\Ride;
use App\Models\Rider;
use App\Services\DriverMatchingService;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Tests\TestCase;

class DriverMatchingConcurrencyTest extends TestCase
{
    use DatabaseMigrations;

    protected function setUp(): void
    {
        parent::setUp();

        $this->assertSame(
            'pgsql',
            config('database.default'),
            'These tests must run against PostgreSQL.'
        );

        $this->assertSame(
            'ride_booking_test',
            config('database.connections.pgsql.database'),
            'Refusing to run against a database other than ride_booking_test.'
        );
    }

    public function test_database_prevents_a_driver_from_having_two_active_assignments(): void
    {
        $rider = Rider::create([
            'name' => 'Concurrency Rider',
            'email' => 'concurrency@example.com',
            'phone' => '03001119999',
        ]);

        $driver = Driver::create([
            'name' => 'Concurrency Driver',
            'phone' => '03001118888',
            'vehicle_category' => 'economy',
            'vehicle_model' => 'Suzuki Alto',
            'vehicle_number' => 'CONC-001',
            'is_available' => true,
        ]);

        $firstRide = $this->createRide($rider);
        $secondRide = $this->createRide($rider);

        app(DriverMatchingService::class)->assignDriver($firstRide);

        $this->expectException(\Illuminate\Database\QueryException::class);

        // Bypass the matching service deliberately to verify
        // the PostgreSQL unique partial index itself.
        \App\Models\RideAssignment::create([
            'ride_id' => $secondRide->id,
            'driver_id' => $driver->id,
            'assigned_at' => now(),
        ]);
    }

    public function test_database_prevents_a_ride_from_having_two_active_assignments(): void
    {
        $rider = Rider::create([
            'name' => 'Ride Constraint Rider',
            'email' => 'ride-constraint@example.com',
            'phone' => '03001117777',
        ]);

        $firstDriver = $this->createDriver('CONC-002', '03001116661');
        $secondDriver = $this->createDriver('CONC-003', '03001116662');

        $ride = $this->createRide($rider);

        \App\Models\RideAssignment::create([
            'ride_id' => $ride->id,
            'driver_id' => $firstDriver->id,
            'assigned_at' => now(),
        ]);

        $this->expectException(\Illuminate\Database\QueryException::class);

        \App\Models\RideAssignment::create([
            'ride_id' => $ride->id,
            'driver_id' => $secondDriver->id,
            'assigned_at' => now(),
        ]);
    }

    private function createRide(Rider $rider): Ride
    {
        return Ride::create([
            'rider_id' => $rider->id,
            'pickup_location' => 'Liberty Market',
            'pickup_latitude' => 31.5100,
            'pickup_longitude' => 74.3440,
            'destination_location' => 'Packages Mall',
            'destination_latitude' => 31.4710,
            'destination_longitude' => 74.3540,
            'vehicle_category' => 'economy',
            'estimated_fare' => 500,
            'estimated_duration_minutes' => 25,
            'status' => 'requested',
            'requested_at' => now(),
        ]);
    }

    private function createDriver(string $vehicleNumber, string $phone): Driver
    {
        return Driver::create([
            'name' => 'Test Driver '.$vehicleNumber,
            'phone' => $phone,
            'vehicle_category' => 'economy',
            'vehicle_model' => 'Suzuki Alto',
            'vehicle_number' => $vehicleNumber,
            'is_available' => true,
        ]);
    }
}
