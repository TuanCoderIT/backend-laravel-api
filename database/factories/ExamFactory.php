<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Exam;
use App\Models\Category;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Exam>
 */
class ExamFactory extends Factory
{
    protected $model = Exam::class;
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $difficultyLevels = ['Beginner', 'Intermediate', 'Advanced'];
        $statuses = ['draft', 'published', 'archived'];

        return [
            'title' => $this->faker->sentence(4),
            'description' => $this->faker->paragraph(3),
            'category_id' => Category::inRandomOrder()->first()->id ?? Category::factory(),
            'difficulty' => $this->faker->randomElement($difficultyLevels),
            'duration' => $this->faker->numberBetween(20, 90),
            'color' => $this->faker->safeHexColor(),
            'passing_score' => $this->faker->numberBetween(50, 90),
            'max_attempts' => $this->faker->numberBetween(3, 10),
            'learning_objectives' => $this->faker->sentences(4),
            'prerequisites' => $this->faker->words(3),
            'tags' => $this->faker->words(3),
            'status' => $this->faker->randomElement($statuses),
        ];
    }
}
