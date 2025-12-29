<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Workday>
 */
class WorkdayFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        // 1. Determine the type first
        $type = $this->faker->boolean(80) 
            ? 'work' 
            : $this->faker->randomElement(['sick', 'holiday']);

        // 2. Define logical Shift Presets
        $shiftPresets = [
            ['start' => '04:30', 'end' => '11:00', 'label' => 'Opening shift'],
            ['start' => '07:15', 'end' => '10:15', 'label' => 'Early shift'],
            ['start' => '11:00', 'end' => '15:30', 'label' => 'Afternoon shift'],
            ['start' => '16:00', 'end' => '21:00', 'label' => 'Late Shift'],
        ];

        // 3. Pick a random preset if it's a workday
        $preset = ($type) 
            ? $this->faker->randomElement($shiftPresets) 
            : null;

        return [
            'type' => $type,
            'label' => $type === 'shift'
                ? 'work' 
                : $this->faker->randomElement(['afgemeld', 'vrij']),
            'start_time' => $preset ? $preset['start'] : null,
            'end_time'   => $preset ? $preset['end'] : null,
        ];
    }
}
