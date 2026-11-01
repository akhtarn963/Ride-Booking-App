<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Ride;
use App\Models\RideAssignment;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class RideLifecycleController extends Controller
{
    public function accept(int $id): JsonResponse
    {
        $ride = DB::transaction(function () use ($id) {
            $ride = Ride::whereKey($id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($ride->status !== Ride::STATUS_REQUESTED) {
                abort(409, 'Only requested rides can be accepted.');
            }

            $assignment = RideAssignment::where('ride_id', $ride->id)
                ->whereNull('released_at')
                ->lockForUpdate()
                ->first();

            if (!$assignment) {
                abort(409, 'This ride has no active driver assignment.');
            }

            $ride->update([
                'status' => Ride::STATUS_ACCEPTED,
                'accepted_at' => now(),
            ]);

            return $ride->fresh()->load('rider', 'activeAssignment.driver');
        }, 3);

        return response()->json([
            'success' => true,
            'message' => 'Ride accepted successfully.',
            'data' => $ride,
        ]);
    }

    public function start(int $id): JsonResponse
    {
        $ride = DB::transaction(function () use ($id) {
            $ride = Ride::whereKey($id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($ride->status !== Ride::STATUS_ACCEPTED) {
                abort(409, 'Only accepted rides can be started.');
            }

            $assignment = RideAssignment::where('ride_id', $ride->id)
                ->whereNull('released_at')
                ->lockForUpdate()
                ->first();

            if (!$assignment) {
                abort(409, 'This ride has no active driver assignment.');
            }

            $ride->update([
                'status' => Ride::STATUS_IN_PROGRESS,
                'started_at' => now(),
            ]);

            return $ride->fresh()->load('rider', 'activeAssignment.driver');
        }, 3);

        return response()->json([
            'success' => true,
            'message' => 'Ride started successfully.',
            'data' => $ride,
        ]);
    }

    public function complete(int $id): JsonResponse
    {
        $ride = DB::transaction(function () use ($id) {
            $ride = Ride::whereKey($id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($ride->status !== Ride::STATUS_IN_PROGRESS) {
                abort(409, 'Only in-progress rides can be completed.');
            }

            $assignment = RideAssignment::where('ride_id', $ride->id)
                ->whereNull('released_at')
                ->lockForUpdate()
                ->first();

            if (!$assignment) {
                abort(409, 'This ride has no active driver assignment.');
            }

            $ride->update([
                'status' => Ride::STATUS_COMPLETED,
                'completed_at' => now(),
                'final_fare' => $ride->estimated_fare,
            ]);

            $assignment->update([
                'released_at' => now(),
            ]);

            $assignment->driver->update([
                'is_available' => true,
            ]);

            return $ride->fresh()->load('rider', 'activeAssignment.driver');
        }, 3);

        return response()->json([
            'success' => true,
            'message' => 'Ride completed successfully.',
            'data' => $ride,
        ]);
    }

    
    public function cancel(int $id): JsonResponse
    {
        $ride = DB::transaction(function () use ($id) {
            $ride = Ride::whereKey($id)
                ->lockForUpdate()
                ->firstOrFail();

            if (!in_array($ride->status, [
                Ride::STATUS_REQUESTED,
                Ride::STATUS_ACCEPTED,
            ], true)) {
                abort(409, 'Only requested or accepted rides can be cancelled.');
            }

            $assignment = RideAssignment::where('ride_id', $ride->id)
                ->whereNull('released_at')
                ->lockForUpdate()
                ->first();

            $ride->update([
                'status' => Ride::STATUS_CANCELLED,
                'cancelled_at' => now(),
            ]);

            if ($assignment) {
                $assignment->update([
                    'released_at' => now(),
                ]);

                $assignment->driver->update([
                    'is_available' => true,
                ]);
            }

            return $ride->fresh()->load('rider', 'activeAssignment.driver');
        }, 3);

        return response()->json([
            'success' => true,
            'message' => 'Ride cancelled successfully.',
            'data' => $ride,
        ]);
    }

}