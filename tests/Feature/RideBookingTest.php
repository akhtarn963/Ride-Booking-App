<?php

namespace Tests\Feature;

use App\Models\Rider;
use App\Models\Driver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RideBookingTest extends TestCase
{
    use RefreshDatabase;

    public function test_ride_booking_endpoint_is_available(): void
    {
        $response = $this->postJson('/api/rides', []);

        $response->assertStatus(422);
    }

    public function test_rider_can_create_a_ride(): void
    {
        $rider = Rider::create([
            'name' => 'Test Rider',
            'email' => 'test@example.com',
            'phone' => '03001112222',
        ]);

        $response = $this->withHeader('Idempotency-Key', 'test-ride-001')
            ->postJson('/api/rides', [
                'rider_id' => $rider->id,
                'pickup_location' => 'Liberty Market',
                'pickup_latitude' => 31.5100,
                'pickup_longitude' => 74.3440,
                'destination_location' => 'Packages Mall',
                'destination_latitude' => 31.4710,
                'destination_longitude' => 74.3540,
                'vehicle_category' => 'economy',
            ]);

        $response->assertCreated()
            ->assertJsonPath('data.status', 'requested')
            ->assertJsonPath('data.vehicle_category', 'economy');

        $this->assertDatabaseCount('rides', 1);
    }

    public function test_repeated_booking_request_does_not_create_duplicate_ride(): void
    {
        $rider = Rider::create([
            'name' => 'Test Rider',
            'email' => 'idempotency@example.com',
            'phone' => '03001112223',
        ]);

        $payload = [
            'rider_id' => $rider->id,
            'pickup_location' => 'Liberty Market',
            'pickup_latitude' => 31.5100,
            'pickup_longitude' => 74.3440,
            'destination_location' => 'Packages Mall',
            'destination_latitude' => 31.4710,
            'destination_longitude' => 74.3540,
            'vehicle_category' => 'economy',
        ];

        $firstResponse = $this->withHeader('Idempotency-Key', 'duplicate-test-001')
            ->postJson('/api/rides', $payload);

        $firstResponse->assertCreated();

        $secondResponse = $this->withHeader('Idempotency-Key', 'duplicate-test-001')
            ->postJson('/api/rides', $payload);

        $secondResponse->assertOk();

        $this->assertDatabaseCount('rides', 1);
        $this->assertDatabaseCount('idempotency_keys', 1);
    }

    public function test_same_idempotency_key_with_different_data_is_rejected(): void
    {
        $rider = Rider::create([
            'name' => 'Test Rider',
            'email' => 'different-data@example.com',
            'phone' => '03001112224',
        ]);

        $payload = [
            'rider_id' => $rider->id,
            'pickup_location' => 'Liberty Market',
            'pickup_latitude' => 31.5100,
            'pickup_longitude' => 74.3440,
            'destination_location' => 'Packages Mall',
            'destination_latitude' => 31.4710,
            'destination_longitude' => 74.3540,
            'vehicle_category' => 'economy',
        ];

        $this->withHeader('Idempotency-Key', 'reuse-key-001')
            ->postJson('/api/rides', $payload)
            ->assertCreated();

        $payload['destination_location'] = 'Emporium Mall';

        $this->withHeader('Idempotency-Key', 'reuse-key-001')
            ->postJson('/api/rides', $payload)
            ->assertUnprocessable();

        $this->assertDatabaseCount('rides', 1);
    }

    public function test_new_ride_is_automatically_assigned_an_available_matching_driver(): void
    {
        $rider = Rider::create([
            'name' => 'Matching Test Rider',
            'email' => 'matching@example.com',
            'phone' => '03001112225',
        ]);

        $driver = Driver::create([
            'name' => 'Test Economy Driver',
            'phone' => '03001112226',
            'vehicle_category' => 'economy',
            'vehicle_model' => 'Suzuki Alto',
            'vehicle_number' => 'TEST-001',
            'is_available' => true,
            'latitude' => 31.5204,
            'longitude' => 74.3587,
        ]);

        $response = $this->withHeader('Idempotency-Key', 'matching-test-001')
            ->postJson('/api/rides', [
                'rider_id' => $rider->id,
                'pickup_location' => 'Liberty Market',
                'pickup_latitude' => 31.5100,
                'pickup_longitude' => 74.3440,
                'destination_location' => 'Packages Mall',
                'destination_latitude' => 31.4710,
                'destination_longitude' => 74.3540,
                'vehicle_category' => 'economy',
            ]);

        $response->assertCreated()
            ->assertJsonPath('data.active_assignment.driver.id', $driver->id);

        $this->assertDatabaseHas('drivers', [
            'id' => $driver->id,
            'is_available' => false,
        ]);

        $this->assertDatabaseCount('ride_assignments', 1);
    }

    public function test_driver_cannot_be_assigned_to_two_active_rides(): void
    {
        $driver = Driver::create([
            'name' => 'Exclusive Test Driver',
            'phone' => '03001112227',
            'vehicle_category' => 'economy',
            'vehicle_model' => 'Suzuki Alto',
            'vehicle_number' => 'TEST-002',
            'is_available' => true,
        ]);

        $rider = Rider::create([
            'name' => 'Exclusive Test Rider',
            'email' => 'exclusive@example.com',
            'phone' => '03001112228',
        ]);

        $firstRide = \App\Models\Ride::create([
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

        $secondRide = \App\Models\Ride::create([
            'rider_id' => $rider->id,
            'pickup_location' => 'Gulberg',
            'pickup_latitude' => 31.5200,
            'pickup_longitude' => 74.3500,
            'destination_location' => 'DHA',
            'destination_latitude' => 31.4700,
            'destination_longitude' => 74.4000,
            'vehicle_category' => 'economy',
            'estimated_fare' => 600,
            'estimated_duration_minutes' => 30,
            'status' => 'requested',
            'requested_at' => now(),
        ]);

        $matchingService = app(\App\Services\DriverMatchingService::class);

        $firstAssignment = $matchingService->assignDriver($firstRide);
        $secondAssignment = $matchingService->assignDriver($secondRide);

        $this->assertNotNull($firstAssignment);
        $this->assertNull($secondAssignment);

        $this->assertDatabaseCount('ride_assignments', 1);
    }

    public function test_ride_can_be_accepted_started_and_completed(): void
    {
        $rider = Rider::create([
            'name' => 'Lifecycle Rider',
            'email' => 'lifecycle@example.com',
            'phone' => '03001112229',
        ]);

        Driver::create([
            'name' => 'Lifecycle Driver',
            'phone' => '03001112230',
            'vehicle_category' => 'economy',
            'vehicle_model' => 'Suzuki Alto',
            'vehicle_number' => 'TEST-003',
            'is_available' => true,
        ]);

        $booking = $this->withHeader('Idempotency-Key', 'lifecycle-test-001')
            ->postJson('/api/rides', [
                'rider_id' => $rider->id,
                'pickup_location' => 'Liberty Market',
                'pickup_latitude' => 31.5100,
                'pickup_longitude' => 74.3440,
                'destination_location' => 'Packages Mall',
                'destination_latitude' => 31.4710,
                'destination_longitude' => 74.3540,
                'vehicle_category' => 'economy',
            ]);

        $booking->assertCreated();

        $rideId = $booking->json('data.id');

        $this->postJson("/api/rides/{$rideId}/accept")
            ->assertOk()
            ->assertJsonPath('data.status', 'accepted');

        $this->postJson("/api/rides/{$rideId}/start")
            ->assertOk()
            ->assertJsonPath('data.status', 'in_progress');

        $this->postJson("/api/rides/{$rideId}/complete")
            ->assertOk()
            ->assertJsonPath('data.status', 'completed');

        $this->assertDatabaseHas('drivers', [
            'phone' => '03001112230',
            'is_available' => true,
        ]);

        $this->assertDatabaseHas('ride_assignments', [
            'ride_id' => $rideId,
        ]);

        $this->assertNotNull(
            \App\Models\RideAssignment::where('ride_id', $rideId)
                ->first()
                ->released_at
        );
    }

    public function test_cancelling_ride_releases_assigned_driver(): void
    {
        $rider = Rider::create([
            'name' => 'Cancellation Rider',
            'email' => 'cancellation@example.com',
            'phone' => '03001112231',
        ]);

        $driver = Driver::create([
            'name' => 'Cancellation Driver',
            'phone' => '03001112232',
            'vehicle_category' => 'economy',
            'vehicle_model' => 'Suzuki Alto',
            'vehicle_number' => 'TEST-004',
            'is_available' => true,
        ]);

        $booking = $this->withHeader('Idempotency-Key', 'cancel-test-001')
            ->postJson('/api/rides', [
                'rider_id' => $rider->id,
                'pickup_location' => 'Liberty Market',
                'pickup_latitude' => 31.5100,
                'pickup_longitude' => 74.3440,
                'destination_location' => 'Packages Mall',
                'destination_latitude' => 31.4710,
                'destination_longitude' => 74.3540,
                'vehicle_category' => 'economy',
            ]);

        $booking->assertCreated();

        $rideId = $booking->json('data.id');

        $this->assertDatabaseHas('drivers', [
            'id' => $driver->id,
            'is_available' => false,
        ]);

        $this->postJson("/api/rides/{$rideId}/cancel")
            ->assertOk()
            ->assertJsonPath('data.status', 'cancelled');

        $this->assertDatabaseHas('drivers', [
            'id' => $driver->id,
            'is_available' => true,
        ]);

        $assignment = \App\Models\RideAssignment::where('ride_id', $rideId)->first();

        $this->assertNotNull($assignment->released_at);

        $this->postJson("/api/rides/{$rideId}/start")
            ->assertStatus(409);
    }

    public function test_fare_estimate_endpoint_returns_economy_fare(): void
    {
        $response = $this->postJson('/api/fare-estimate', [
            'pickup_latitude' => 31.5204,
            'pickup_longitude' => 74.3587,
            'destination_latitude' => 31.4697,
            'destination_longitude' => 74.2728,
            'vehicle_category' => 'economy',
        ]);

        $response->assertOk()
            ->assertJsonPath('data.currency', 'PKR')
            ->assertJsonPath('data.vehicle_category', 'economy')
            ->assertJsonStructure([
                'data' => [
                    'distance_km',
                    'estimated_fare',
                    'estimated_duration_minutes',
                    'currency',
                    'vehicle_category',
                ],
            ]);
    }

    public function test_ride_remains_requested_when_no_driver_is_available(): void
    {
        $rider = Rider::create([
            'name' => 'Test Rider',
            'email' => 'no-driver@example.com',
            'phone' => '03001112222',
        ]);

        $response = $this->postJson('/api/rides', [
            'rider_id' => $rider->id,
            'pickup_location' => 'Liberty Market',
            'destination_location' => 'Packages Mall',
            'pickup_latitude' => 31.5120,
            'pickup_longitude' => 74.3440,
            'destination_latitude' => 31.4697,
            'destination_longitude' => 74.2728,
            'vehicle_category' => 'economy',
        ], [
            'Idempotency-Key' => 'no-driver-test-001',
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.status', 'requested')
            ->assertJsonPath('data.active_assignment', null);

        $this->assertDatabaseCount('rides', 1);
        $this->assertDatabaseCount('ride_assignments', 0);
    }

    public function test_requested_ride_cannot_be_started_before_acceptance(): void
    {
        $rider = Rider::create([
            'name' => 'Test Rider',
            'email' => 'invalid-transition@example.com',
            'phone' => '03001113333',
        ]);

        $response = $this->postJson('/api/rides', [
            'rider_id' => $rider->id,
            'pickup_location' => 'Liberty Market',
            'destination_location' => 'Packages Mall',
            'pickup_latitude' => 31.5120,
            'pickup_longitude' => 74.3440,
            'destination_latitude' => 31.4697,
            'destination_longitude' => 74.2728,
            'vehicle_category' => 'economy',
        ], [
            'Idempotency-Key' => 'invalid-transition-001',
        ]);

        $rideId = $response->json('data.id');

        $response->assertCreated();

        $this->postJson("/api/rides/{$rideId}/start")
            ->assertStatus(409);

        $this->assertDatabaseHas('rides', [
            'id' => $rideId,
            'status' => 'requested',
        ]);
    }
}