<?php

namespace Database\Seeders;

use App\Models\StoreHour;
use Illuminate\Database\Seeder;

class StoreHoursSeeder extends Seeder
{
    public function run(): void
    {
        // 0 = Sunday, 1 = Monday ... 6 = Saturday
        // Mon-Sat: 09:00 - 19:00, Sun: Closed
        $schedule = [
            0 => null, // Closed
            1 => ['09:00', '19:00'],
            2 => ['09:00', '19:00'],
            3 => ['09:00', '19:00'],
            4 => ['09:00', '19:00'],
            5 => ['09:00', '19:00'],
            6 => ['09:00', '19:00'],
        ];

        foreach ($schedule as $weekday => $hours) {
            StoreHour::updateOrCreate(
                ['weekday' => $weekday],
                [
                    'opens_at' => $hours[0] ?? null,
                    'closes_at' => $hours[1] ?? null,
                    'is_closed' => $hours === null,
                ]
            );
        }
    }
}
