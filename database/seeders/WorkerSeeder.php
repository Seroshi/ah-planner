<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Worker;
use App\Models\Workday;

class WorkerSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $today = now(); 
        
        //Fake people used for this demo
        $names = ['Rudolf Render', 'Laura van Dijk', 'David van den Brug', 'Pieter Boom', 
            'Hendrik van Oogen', 'Tessa Boogschutter'];
    
        foreach($names as $name)
        {
            $worker = Worker::factory()->withName($name)->create();

            // Create 30 days in the past (so the calendar looks used)
            for ($i = 30; $i > 0; $i--) {
                // 70% chance to create a day, 30% chance to skip (leave it empty)
                if (rand(1, 100) <= 70) {
                    Workday::factory()->create([
                        'worker_id' => $worker->id,
                        'date' => $today->copy()->subDays($i)->toDateString(),
                    ]);
                }
            }

            // Create 14 days in the future (ahead in the calendar)
            for ($i = 0; $i < 14; $i++) {
                if (rand(1, 100) <= 70) {
                    Workday::factory()->create([
                        'worker_id' => $worker->id,
                        'date' => $today->copy()->addDays($i)->toDateString(),
                    ]);
                }
            }
        }
    }
}
