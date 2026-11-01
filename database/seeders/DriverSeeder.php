<?php

namespace Database\Seeders;

use App\Models\Driver;
use Illuminate\Database\Seeder;

class DriverSeeder extends Seeder
{
    public function run(): void
    {
        $drivers = [
            [
                'name' => 'Bilal Ahmed',
                'phone' => '03111234567',
                'vehicle_category' => 'economy',
                'vehicle_model' => 'Suzuki Alto',
                'vehicle_number' => 'LEA-1234',
                'is_available' => true,
                'latitude' => 31.5204,
                'longitude' => 74.3587,
            ],
            [
                'name' => 'Hassan Ali',
                'phone' => '03117654321',
                'vehicle_category' => 'comfort',
                'vehicle_model' => 'Honda Civic',
                'vehicle_number' => 'LEB-5678',
                'is_available' => true,
                'latitude' => 31.4697,
                'longitude' => 74.2728,
            ],
            [
                'name' => 'Usman Malik',
                'phone' => '03119876543',
                'vehicle_category' => 'economy',
                'vehicle_model' => 'Suzuki Wagon R',
                'vehicle_number' => 'LEC-9012',
                'is_available' => true,
                'latitude' => 31.5497,
                'longitude' => 74.3436,
            ],
            [
                'name' => 'Kamran Shah',
                'phone' => '03115551234',
                'vehicle_category' => 'comfort',
                'vehicle_model' => 'Toyota Corolla',
                'vehicle_number' => 'LED-3456',
                'is_available' => true,
                'latitude' => 31.4822,
                'longitude' => 74.3239,
            ],
        ];

        foreach ($drivers as $driver) {
            Driver::updateOrCreate(
                ['phone' => $driver['phone']],
                $driver
            );
        }
    }
}