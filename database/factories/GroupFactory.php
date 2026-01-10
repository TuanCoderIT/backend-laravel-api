<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use App\Models\User;

class GroupFactory extends Factory
{
    public function definition()
    {
        $name = fake()->words(3, true);

        return [
            'name' => $name,
            'slug' => Str::slug($name) . '-' . uniqid(),
            'description' => fake()->sentence(),
            'cover_image' => fake()->imageUrl(),
            'owner_id' => User::inRandomOrder()->first()->id,
            'visibility' => 'public',
            'members_count' => 1,
        ];
    }
}
