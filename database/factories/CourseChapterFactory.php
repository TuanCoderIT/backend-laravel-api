<?php

namespace Database\Factories;

use App\Models\CourseChapter;
use Illuminate\Database\Eloquent\Factories\Factory;

class CourseChapterFactory extends Factory
{
    protected $model = CourseChapter::class;

    public function definition(): array
    {
        return [
            'title' => $this->faker->sentence(3),
            'description' => $this->faker->paragraph(2),
            'order' => 0, // sẽ set lại khi seed
        ];
    }
}
