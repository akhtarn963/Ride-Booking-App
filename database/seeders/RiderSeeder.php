<?php

namespace Database\Seeders;

use App\Models\Rider;
use Illuminate\Database\Seeder;

class RiderSeeder extends Seeder
{
    public function run(): void
    {
        $riders = [
            [
                'name' => 'Ahmed Ali',
                'email' => 'ahmed@example.com',
                'phone' => '03001234567',
            ],
            [
                'name' => 'Usman Khan',
                'email' => 'usman@example.com',
                'phone' => '03007654321',
            ],
            [
                'name' => 'Sara Ahmed',
                'email' => 'sara@example.com',
                'phone' => '03009876543',
            ],
        ];

        foreach ($riders as $rider) {
            Rider::updateOrCreate(
                ['email' => $rider['email']],
                $rider
            );
        }
    }
}