<?php

namespace Database\Factories;

use App\Models\Division;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Division>
 */
class DivisionFactory extends Factory
{
    protected $model = Division::class;

    public function definition(): array
    {
        return [
            'name' => fake()->unique()->randomElement([
                'Operations',
                'Product',
                'Engineering',
                'Customer Success',
                'Finance',
                'People',
            ]),
            'description' => fake()->sentence(),
            'is_active' => true,
        ];
    }
}
