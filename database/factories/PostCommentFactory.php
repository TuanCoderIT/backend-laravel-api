<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Post;
use App\Models\User;

class PostCommentFactory extends Factory
{
    public function definition()
    {
        return [
            'post_id'   => Post::inRandomOrder()->first()->id,
            'user_id'   => User::inRandomOrder()->first()->id,
            'content'   => fake()->sentence(),
            'parent_id' => null,
        ];
    }
}
