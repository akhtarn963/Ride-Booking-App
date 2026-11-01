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
        Schema::create('ride_assignments', function (Blueprint $table) {
            $table->id();

            $table->foreignId('ride_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('driver_id')
                ->constrained()
                ->restrictOnDelete();

            $table->timestamp('assigned_at')->useCurrent();
            $table->timestamp('released_at')->nullable();

            $table->timestamps();

            $table->index(['driver_id', 'released_at']);
            $table->index(['ride_id', 'released_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ride_assignments');
    }
};
