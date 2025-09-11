<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Question;
use App\Models\Exam;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Nếu muốn giữ User mẫu thì để lại:
        // \App\Models\User::factory(10)->create();
        // \App\Models\Exam::factory(100)->create();

        // Hoặc xoá nếu không cần:
        // \App\Models\User::factory()->create([
        //     'name' => 'Test User',
        //     'email' => 'test@example.com',
        // ]);

        // Thêm Seeder bạn cần gọi:
        $this->call([
            // CategorySeeder::class,
            // ExamSeeder::class,
            QuestionSeeder::class,
            // ExamQuestionSeeder::class,
            // TokenSeeder::class,
        ]);
    }
}
