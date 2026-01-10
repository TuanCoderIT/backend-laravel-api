<?php

namespace Database\Factories;

use App\Models\CourseLesson;
use Illuminate\Database\Eloquent\Factories\Factory;

class CourseLessonFactory extends Factory
{
    protected $model = CourseLesson::class;

    public function definition(): array
    {
        return [
            'title' => $this->faker->sentence(4),
            'content' => $this->faker->paragraph(3),
            'order' => 0, // sẽ set lại khi seed
            'is_free_preview' => $this->faker->boolean(20), // 20% học thử
        ];
    }
}
