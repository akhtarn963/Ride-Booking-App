<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Ride;
use App\Services\RideBookingService;
use App\Services\DriverMatchingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class RideController extends Controller
{
    public function store(
    Request $request,
    RideBookingService $bookingService,
    DriverMatchingService $matchingService
): JsonResponse {
        $validated = $request->validate([
            'rider_id' => ['required', 'integer', 'exists:riders,id'],

            'pickup_location' => ['required', 'string', 'max:255'],
            'pickup_latitude' => ['required', 'numeric', 'between:-90,90'],
            'pickup_longitude' => ['required', 'numeric', 'between:-180,180'],

            'destination_location' => ['required', 'string', 'max:255'],
            'destination_latitude' => ['required', 'numeric', 'between:-90,90'],
            'destination_longitude' => ['required', 'numeric', 'between:-180,180'],

            'vehicle_category' => [
                'required',
                Rule::in(['economy', 'comfort']),
            ],
        ]);

        $idempotencyKey = $request->header('Idempotency-Key');

        if (!$idempotencyKey || strlen($idempotencyKey) > 100) {
            return response()->json([
                'success' => false,
                'message' => 'A valid Idempotency-Key header is required.',
            ], 400);
        }

        $result = $bookingService->createRide(
            $validated,
            $idempotencyKey
        );
        $assignment = $matchingService->assignDriver($result['ride']);

        return response()->json([
            'success' => true,
            'message' => $result['created']
                ? 'Ride requested successfully.'
                : 'Existing ride returned.',
            'data' => $result['ride']->fresh()->load('rider', 'activeAssignment.driver'),
        ], $result['created'] ? 201 : 200);
    }

    public function show(int $id): JsonResponse
    {
        $ride = Ride::with([
            'rider',
            'activeAssignment.driver',
        ])->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $ride,
        ]);
    }
}