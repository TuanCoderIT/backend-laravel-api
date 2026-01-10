<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Category;

class DocumentFactory extends Factory
{
    public function definition(): array
    {
        $isPremium = $this->faker->boolean(30); // 30% premium

        return [
            'owner_id' => User::inRandomOrder()->value('id') ?? User::factory(),
            'title' => ucfirst($this->faker->sentence(rand(3, 6))),
            'description' => $this->faker->paragraph(rand(1, 3)),
            'is_premium' => $isPremium,
            'status' => $this->faker->randomElement(['draft', 'published']),
            'created_at' => $this->faker->dateTimeBetween('-3 months', 'now'),
            'category_id' => Category::inRandomOrder()->first()->id ?? Category::factory(),
        ];
    }
}
