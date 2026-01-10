<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\User;
use App\Models\Group;

class PostFactory extends Factory
{
    public function definition()
    {
        return [
            'user_id'     => User::inRandomOrder()->first()->id,
            'group_id'    => rand(0, 1) ? Group::inRandomOrder()->first()?->id : null,
            'content'     => fake()->sentence(12),
            'attachments' => rand(0, 1) ? [['url' => fake()->imageUrl()]] : null,
            'visibility'  => 'public',
            'is_pinned'   => false,
        ];
    }
}
