<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Category;

class CategorySeeder extends Seeder
{
    public function run()
    {
        Category::insert([
            ['id' => 1, 'name' => 'Programming'],
            ['id' => 2, 'name' => 'Data Science'],
            ['id' => 3, 'name' => 'Database'],
            ['id' => 4, 'name' => 'Networking'],
        ]);
    }
}
