<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\{User, Document};

class DocumentSeeder extends Seeder
{
    public function run(): void
    {
        Document::factory(100)->create();
    }
}
