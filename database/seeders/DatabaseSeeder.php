<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Question;
use App\Models\Exam;
use App\Models\Group;
use App\Models\Post;
use App\Models\PostComment;
use App\Models\Reaction;
use Database\Seeders\RandomChatSeeder;
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
        // Tạo 3 nhóm
        // Group::factory()->count(3)->create();

        // Tạo 10 bài post
        // Post::factory()->count(10)->create();

        // Tạo comment (5)
        // PostComment::factory()->count(5)->create();

        // Tạo reaction (10)
        // Reaction::factory()->count(10)->create();
        // Hoặc xoá nếu không cần:
        // \App\Models\User::factory()->create([
        //     'name' => 'Test User',
        //     'email' => 'test@example.com',
        // ]);

        // Thêm Seeder bạn cần gọi:
        // $this->call([
        //         // CategorySeeder::class,
        //         // ExamSeeder::class,
        //         // QuestionSeeder::class,
        //         // DocumentSeeder::class,
        //         // ExamQuestionSeeder::class,
        //         // TokenSeeder::class,  
        //         // UserSeeder::class,
        //         // CourseSeeder::class,
        //     // ChapterLessonSeeder::class,
        $this->call([
            // RandomChatSeeder::class,
            // TransactionSeeder::class,
            // AchievementSeeder::class,
        ]);
        // ]);
    }
}
