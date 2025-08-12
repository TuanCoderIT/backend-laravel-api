<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Transaction;
use App\Models\User;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Transaction>
 */
class TransactionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    protected $model = Transaction::class;

    public function definition(): array
    {
        return [
            'type' => $this->faker->randomElement(['top_up', 'spend', 'reward']),
            'amount' => $this->faker->numberBetween(1, 100),
            'description' => $this->faker->optional()->sentence(),
            'metadata' => null,
        ];
    }
}
