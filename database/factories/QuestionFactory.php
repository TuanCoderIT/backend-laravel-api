<?php

namespace Database\Factories;

use App\Models\Question;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Question>
 */
class QuestionFactory extends Factory
{
    protected $model = Question::class;
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'content' => $this->faker->sentence(12),
            'options' => [],
            'answer' => '',
            'explanation' => $this->faker->boolean(50) ? $this->faker->sentence(10) : null,
            'type' => 'multiple_choice', // mặc định
            'points' => $this->faker->numberBetween(1, 5),
        ];
    }

    public function multipleChoice()
    {
        return $this->state(function () {
            return [
                'type' => 'multiple_choice',
                'options' => [
                    'A' => $this->faker->word(),
                    'B' => $this->faker->word(),
                    'C' => $this->faker->word(),
                    'D' => $this->faker->word(),
                ],
                'answer' => $this->faker->randomElement(['A', 'B', 'C', 'D']),
            ];
        });
    }

    public function trueFalse()
    {
        return $this->state(function () {
            return [
                'type' => 'true_false',
                'options' => ['A' => 'True', 'B' => 'False'],
                'answer' => $this->faker->randomElement(['A', 'B']),
            ];
        });
    }

    public function shortAnswer()
    {
        return $this->state(function () {
            return [
                'type' => 'short_answer',
                'options' => [],
                'answer' => $this->faker->words(2, true),
            ];
        });
    }

    public function essay()
    {
        return $this->state(function () {
            return [
                'type' => 'essay',
                'options' => [],
                'answer' => 'Essay grading required',
            ];
        });
    }
}
