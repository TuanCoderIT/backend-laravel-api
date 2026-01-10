<?php

namespace Database\Factories;

use App\Models\Course;
use App\Models\User;
use App\Models\Category;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class CourseFactory extends Factory
{
    protected $model = Course::class;

    public function definition(): array
    {
        $title = $this->faker->unique()->sentence(4);
        return [
            'user_id' => User::where('role', 'instructor')->inRandomOrder()->value('id')
                ?? User::factory()->create(['role' => 'instructor'])->id,
            'category_id' => Category::inRandomOrder()->value('id'),
            'title' => $title,
            'slug' => Str::slug($title) . '-' . Str::random(5),
            'description' => $this->faker->paragraph(4),
            'thumbnail' => $this->faker->imageUrl(640, 480, 'education', true),
            'is_public' => $this->faker->boolean(80), // 80% công khai
        ];
    }
}
