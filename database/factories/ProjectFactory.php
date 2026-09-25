<?php

namespace Database\Factories;

use App\Models\Project;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Project>
 */
class ProjectFactory extends Factory
{
    protected $model = Project::class;

    public function definition(): array
    {
        $startDate = fake()->dateTimeBetween('-2 months', '+1 month');

        return [
            'name' => fake()->catchPhrase(),
            'description' => fake()->paragraph(),
            'ticket_prefix' => strtoupper(fake()->unique()->lexify('???')),
            'status' => fake()->randomElement(['Draft', 'In Progress', 'Completed']),
            'color' => fake()->safeHexColor(),
            'start_date' => $startDate,
            'end_date' => fake()->dateTimeBetween($startDate, '+5 months'),
        ];
    }
}
