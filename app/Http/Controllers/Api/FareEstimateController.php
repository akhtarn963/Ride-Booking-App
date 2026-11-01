<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\FareEstimateService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class FareEstimateController extends Controller
{
    public function __invoke(
        Request $request,
        FareEstimateService $fareService
    ): JsonResponse {
        $validated = $request->validate([
            'pickup_latitude' => ['required', 'numeric', 'between:-90,90'],
            'pickup_longitude' => ['required', 'numeric', 'between:-180,180'],
            'destination_latitude' => ['required', 'numeric', 'between:-90,90'],
            'destination_longitude' => ['required', 'numeric', 'between:-180,180'],
            'vehicle_category' => [
                'required',
                Rule::in(['economy', 'comfort']),
            ],
        ]);

        $estimate = $fareService->estimate(
            (float) $validated['pickup_latitude'],
            (float) $validated['pickup_longitude'],
            (float) $validated['destination_latitude'],
            (float) $validated['destination_longitude'],
            $validated['vehicle_category']
        );

        return response()->json([
            'success' => true,
            'data' => $estimate,
        ]);
    }
}