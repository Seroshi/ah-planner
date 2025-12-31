<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Workday;
use Carbon\Carbon;

class WorkdaySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $today = now(); 

        // Create 30 days in the past (so the calendar looks used)
        for ($i = 30; $i > 0; $i--) {
            // 70% chance to create a day, 30% chance to skip (leave it empty)
            if (rand(1, 100) <= 70) {
                Workday::factory()->create([
                    'date' => $today->copy()->subDays($i)->toDateString(),
                ]);
            }
        }

        // Create 14 days in the future (showing upcoming schedule)
        for ($i = 0; $i < 14; $i++) {
            if (rand(1, 100) <= 70) {
                Workday::factory()->create([
                    'date' => $today->copy()->addDays($i)->toDateString(),
                ]);
            }
        }
    }
}
