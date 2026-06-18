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
    public function run(): void
    {
        // Hoặc xoá nếu không cần:
        \App\Models\User::firstOrCreate(
            ['email' => 'test@example.com'],
            ['name' => 'Test User', 'password' => bcrypt('password')]
        );

        // Thêm Seeder bạn cần gọi:
        $this->call([
            UserSeeder::class,
            CategorySeeder::class,
            ExamSeeder::class,
            QuestionSeeder::class,
            DocumentSeeder::class,
            ExamQuestionSeeder::class,
            TokenSeeder::class,
            CourseSeeder::class,
            ChapterLessonSeeder::class,
        ]);

        // Tạo dữ liệu cộng đồng sau khi đã có users.
        Group::factory()->count(3)->create();
        Post::factory()->count(10)->create();
        PostComment::factory()->count(5)->create();
        Reaction::factory()->count(10)->create();

        $this->call([
            RandomChatSeeder::class,
            TransactionSeeder::class,
            AchievementSeeder::class,
        ]);
    }
}
