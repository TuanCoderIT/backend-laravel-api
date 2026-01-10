<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\PurchaseLog>
 */
class PurchaseLogFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $types = ['quiz', 'document'];
        return [
            'target_type' => $this->faker->randomElement($types),
            'target_id' => $this->faker->numberBetween(1, 20),
            'token_spent' => $this->faker->randomElement([5, 10, 15]),
        ];
    }
}
