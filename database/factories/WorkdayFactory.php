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
        // Define presets here so they are available to all attributes
        $presets = [
            ['start' => '04:30', 'end' => '11:00', 'label' => 'Opening shift'],
            ['start' => '07:15', 'end' => '10:15', 'label' => 'Early shift'],
            ['start' => '11:00', 'end' => '15:30', 'label' => 'Afternoon shift'],
            ['start' => '16:00', 'end' => '21:00', 'label' => 'Late Shift'],
        ];

        $preset = $this->faker->randomElement($presets);

        return [
            'date' => $this->faker->date(),
            'type' => function (array $attributes) {
                $isPast = \Carbon\Carbon::parse($attributes['date'])->isPast();
                if ($this->faker->boolean(85)) return 'work'; //85% chance of work
                return $isPast ? $this->faker->randomElement(['sick', 'holiday']) : 'holiday';
            },
            // If type is work, use the preset. If not, null everything.
            'start_time' => $preset['start'],
            'end_time'   => $preset['end'],
            'label'      => function (array $attributes) use ($preset) {
                if ($attributes['type'] === 'work') return $preset['label'];
                return $attributes['type'] === 'sick' ? 'Afgemeld' : 'Vrij';
            },
        ];
    }
}
