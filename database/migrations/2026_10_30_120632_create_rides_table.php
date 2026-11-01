<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('rides', function (Blueprint $table) {
            $table->id();

            $table->foreignId('rider_id')
                ->constrained()
                ->restrictOnDelete();

            $table->string('pickup_location');
            $table->decimal('pickup_latitude', 10, 7)->nullable();
            $table->decimal('pickup_longitude', 10, 7)->nullable();

            $table->string('destination_location');
            $table->decimal('destination_latitude', 10, 7)->nullable();
            $table->decimal('destination_longitude', 10, 7)->nullable();

            $table->string('vehicle_category'); // economy, comfort

            $table->decimal('estimated_fare', 10, 2);
            $table->unsignedInteger('estimated_duration_minutes');

            $table->decimal('final_fare', 10, 2)->nullable();

            $table->string('status')->default('requested');
            // requested, accepted, in_progress, completed, cancelled

            $table->timestamp('requested_at')->useCurrent();
            $table->timestamp('accepted_at')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();

            $table->timestamps();

            $table->index(['rider_id', 'created_at']);
            $table->index(['status', 'vehicle_category']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rides');
    }
};
