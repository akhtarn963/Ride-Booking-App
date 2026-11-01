<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Vehicle category constraints.
        if (DB::getDriverName() === 'pgsql') {
            DB::statement("
                ALTER TABLE drivers
                ADD CONSTRAINT drivers_vehicle_category_check
                CHECK (vehicle_category IN ('economy', 'comfort'))
            ");

            DB::statement("
                ALTER TABLE rides
                ADD CONSTRAINT rides_vehicle_category_check
                CHECK (vehicle_category IN ('economy', 'comfort'))
            ");

            DB::statement("
                ALTER TABLE rides
                ADD CONSTRAINT rides_status_check
                CHECK (status IN (
                    'requested',
                    'accepted',
                    'in_progress',
                    'completed',
                    'cancelled'
                ))
            ");
        }

        // Active assignment uniqueness.
        DB::statement("
            CREATE UNIQUE INDEX unique_active_driver_assignment
            ON ride_assignments (driver_id)
            WHERE released_at IS NULL
        ");

        DB::statement("
            CREATE UNIQUE INDEX unique_active_ride_assignment
            ON ride_assignments (ride_id)
            WHERE released_at IS NULL
        ");
    }

    public function down(): void
    {
        DB::statement('DROP INDEX IF EXISTS unique_active_driver_assignment');
        DB::statement('DROP INDEX IF EXISTS unique_active_ride_assignment');

        if (DB::getDriverName() === 'pgsql') {
            DB::statement("
                ALTER TABLE rides
                DROP CONSTRAINT IF EXISTS rides_status_check
            ");

            DB::statement("
                ALTER TABLE rides
                DROP CONSTRAINT IF EXISTS rides_vehicle_category_check
            ");

            DB::statement("
                ALTER TABLE drivers
                DROP CONSTRAINT IF EXISTS drivers_vehicle_category_check
            ");
        }
    }
};