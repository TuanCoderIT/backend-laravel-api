<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Achievement;

class AchievementSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $achievements = [
            [
                'code' => 'first_quiz',
                'name' => 'Khởi đầu trắc nghiệm',
                'description' => 'Hoàn thành bài trắc nghiệm (quiz) đầu tiên.',
                'icon' => 'quiz_first',
                'type' => 'quiz_completed',
                'rarity' => 'common',
                'target_value' => 1,
                'xp_reward' => 100,
                'token_reward' => 0,
                'is_active' => true,
            ],
            [
                'code' => 'perfect_score',
                'name' => 'Điểm số tuyệt đối',
                'description' => 'Đạt điểm tối đa 100% trong một bài trắc nghiệm.',
                'icon' => 'quiz_perfect',
                'type' => 'perfect_score',
                'rarity' => 'rare',
                'target_value' => 1,
                'xp_reward' => 250,
                'token_reward' => 0,
                'is_active' => true,
            ],
            [
                'code' => 'quiz_rookie',
                'name' => 'Tập sự trắc nghiệm',
                'description' => 'Hoàn thành 5 bài trắc nghiệm.',
                'icon' => 'quiz_rookie',
                'type' => 'quiz_completed',
                'rarity' => 'common',
                'target_value' => 5,
                'xp_reward' => 150,
                'token_reward' => 0,
                'is_active' => true,
            ],
            [
                'code' => 'quiz_master',
                'name' => 'Bậc thầy trắc nghiệm',
                'description' => 'Hoàn thành 20 bài trắc nghiệm.',
                'icon' => 'quiz_master',
                'type' => 'quiz_completed',
                'rarity' => 'epic',
                'target_value' => 20,
                'xp_reward' => 500,
                'token_reward' => 0,
                'is_active' => true,
            ],
            [
                'code' => 'streak_3_days',
                'name' => 'Kiên trì 3 ngày',
                'description' => 'Duy trì chuỗi học tập liên tục trong 3 ngày.',
                'icon' => 'streak_3',
                'type' => 'streak',
                'rarity' => 'common',
                'target_value' => 3,
                'xp_reward' => 100,
                'token_reward' => 0,
                'is_active' => true,
            ],
            [
                'code' => 'streak_7_days',
                'name' => 'Tuần học tập rực lửa',
                'description' => 'Duy trì chuỗi học tập liên tục trong 7 ngày.',
                'icon' => 'streak_7',
                'type' => 'streak',
                'rarity' => 'rare',
                'target_value' => 7,
                'xp_reward' => 250,
                'token_reward' => 0,
                'is_active' => true,
            ],
            [
                'code' => 'streak_10_days',
                'name' => 'Không thể cản phá',
                'description' => 'Duy trì chuỗi học tập liên tục trong 10 ngày.',
                'icon' => 'streak_10',
                'type' => 'streak',
                'rarity' => 'epic',
                'target_value' => 10,
                'xp_reward' => 500,
                'token_reward' => 0,
                'is_active' => true,
            ],
            [
                'code' => 'flashcard_starter',
                'name' => 'Nhập môn Flashcard',
                'description' => 'Hoàn thành lượt review flashcard đầu tiên.',
                'icon' => 'flashcard_starter',
                'type' => 'flashcard_review',
                'rarity' => 'common',
                'target_value' => 1,
                'xp_reward' => 50,
                'token_reward' => 0,
                'is_active' => true,
            ],
            [
                'code' => 'flashcard_master',
                'name' => 'Bậc thầy ghi nhớ',
                'description' => 'Hoàn thành 50 lượt review flashcard.',
                'icon' => 'flashcard_master',
                'type' => 'flashcard_review',
                'rarity' => 'rare',
                'target_value' => 50,
                'xp_reward' => 300,
                'token_reward' => 0,
                'is_active' => true,
            ],
            [
                'code' => 'wrong_answer_reviewer',
                'name' => 'Học từ sai lầm',
                'description' => 'Tạo bộ flashcard ôn tập từ các câu trả lời sai trong bài trắc nghiệm.',
                'icon' => 'wrong_answer_reviewer',
                'type' => 'wrong_answer_flashcards',
                'rarity' => 'common',
                'target_value' => 1,
                'xp_reward' => 100,
                'token_reward' => 0,
                'is_active' => true,
            ],
        ];

        foreach ($achievements as $data) {
            Achievement::updateOrCreate(
                ['code' => $data['code']],
                $data
            );
        }
    }
}
