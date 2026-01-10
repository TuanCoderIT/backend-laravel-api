<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\User;
use App\Models\Post;

class ReactionFactory extends Factory
{
    public function definition()
    {
        $post = Post::inRandomOrder()->first();

        return [
            'reactionable_id' => $post->id,
            'reactionable_type' => Post::class,
            'user_id' => User::inRandomOrder()->first()->id,
            'reaction_type' => fake()->randomElement(['like', 'love', 'haha', 'sad', 'angry']),
        ];
    }
}
