<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Category;
use Illuminate\Support\Str;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            ['name' => 'Programming', 'color' => '#34d399'], // xanh lá
            ['name' => 'Languages', 'color' => '#a78bfa'],  // tím
            ['name' => 'Science', 'color' => '#60a5fa'],     // xanh dương
            ['name' => 'Mathematics', 'color' => '#f87171'], // đỏ
            ['name' => 'History', 'color' => '#fbbf24'],    // vàng
        ];

        foreach ($categories as $cat) {
            Category::updateOrCreate(
                ['name' => $cat['name']],
                [
                    'slug' => Str::slug($cat['name']),
                    'color' => $cat['color'],
                    'is_active' => true,
                ]
            );
        }
    }
}
