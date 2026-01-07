<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Worker>
 */
class WorkerFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => \Str::random(8),
        ];
    }

    public function withName(string $name): static
    {
        return $this->state(function (array $attributes) use ($name) {
            $parts = preg_split('/\s+/', trim($name));
            $count = count($parts);

            return [
                'first_name'  => $parts[0],
                'middle_part' => ($count > 2) ? implode(' ', array_slice($parts, 1, -1)) : null,
                'last_name'   => ($count > 1) ? end($parts) : '',
                'full_name'   => preg_replace('/\s+/', ' ', trim($name)),
            ];
        });
    }
}
