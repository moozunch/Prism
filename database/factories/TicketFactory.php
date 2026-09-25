<?php

namespace Database\Factories;

use App\Models\Ticket;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Ticket>
 */
class TicketFactory extends Factory
{
    protected $model = Ticket::class;

    public function definition(): array
    {
        return [
            'project_id' => null,
            'ticket_status_id' => null,
            'priority_id' => null,
            'name' => fake()->sentence(5),
            'description' => fake()->paragraph(),
            'start_date' => fake()->dateTimeBetween('-1 month', 'now'),
            'due_date' => fake()->dateTimeBetween('now', '+3 months'),
            'created_by' => null,
        ];
    }
}
