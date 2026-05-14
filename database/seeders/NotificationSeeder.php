<?php

namespace Database\Seeders;

use App\Models\Notification;
use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Database\Seeder;

class NotificationSeeder extends Seeder
{
    public function run(): void
    {
        $userIds = User::pluck('id')->toArray();
        
        if (empty($userIds)) {
            $this->command->warn('Không có user nào. Vui lòng seed users trước.');
            return;
        }

        $notificationService = new NotificationService();

        // Sample notifications
        $sampleNotifications = [
            [
                'user_id' => fake()->randomElement($userIds),
                'type' => 'new_message',
                'data' => [
                    'title' => 'Tin nhắn mới',
                    'message' => 'Bạn có tin nhắn mới từ John Doe',
                    'icon' => '💬',
                    'action_url' => '/chat/threads/1',
                    'extra_data' => [
                        'thread_id' => 1,
                        'sender_name' => 'John Doe'
                    ]
                ]
            ],
            [
                'user_id' => fake()->randomElement($userIds),
                'type' => 'course_completed',
                'data' => [
                    'title' => 'Hoàn thành khóa học',
                    'message' => 'Chúc mừng! Bạn đã hoàn thành khóa học "Lập trình PHP"',
                    'icon' => '🎉',
                    'action_url' => '/courses/lap-trinh-php',
                    'extra_data' => [
                        'course_id' => 1,
                        'completion_rate' => 100
                    ]
                ]
            ],
            [
                'user_id' => fake()->randomElement($userIds),
                'type' => 'quiz_completed',
                'data' => [
                    'title' => 'Đạt điểm quiz',
                    'message' => 'Chúc mừng! Bạn đã đạt 85% trong quiz "JavaScript Basics"',
                    'icon' => '🏆',
                    'action_url' => '/exams/1/results',
                    'extra_data' => [
                        'quiz_id' => 1,
                        'score' => 85,
                        'passed' => true
                    ]
                ]
            ],
            [
                'user_id' => fake()->randomElement($userIds),
                'type' => 'ai_quiz_generated',
                'data' => [
                    'title' => 'AI tạo quiz thành công',
                    'message' => 'Quiz "Machine Learning Basics" đã được tạo từ file PDF của bạn',
                    'icon' => '🤖',
                    'action_url' => '/exams/5',
                    'extra_data' => [
                        'quiz_id' => 5,
                        'questions_count' => 10
                    ]
                ]
            ],
            [
                'user_id' => fake()->randomElement($userIds),
                'type' => 'token_reward',
                'data' => [
                    'title' => 'Nhận thưởng token',
                    'message' => 'Bạn đã nhận 1000 tokens từ hoàn thành khóa học',
                    'icon' => '🪙',
                    'action_url' => '/wallet',
                    'extra_data' => [
                        'amount' => 1000,
                        'reason' => 'course_completion'
                    ]
                ]
            ],
            [
                'user_id' => fake()->randomElement($userIds),
                'type' => 'joined_group',
                'data' => [
                    'title' => 'Tham gia nhóm mới',
                    'message' => 'Bạn đã tham gia nhóm "Lập trình viên PHP"',
                    'icon' => '👥',
                    'action_url' => '/groups/lap-trinh-vien-php',
                    'extra_data' => [
                        'group_id' => 1,
                        'group_name' => 'Lập trình viên PHP'
                    ]
                ]
            ],
            [
                'user_id' => fake()->randomElement($userIds),
                'type' => 'system_announcement',
                'data' => [
                    'title' => 'Cập nhật hệ thống',
                    'message' => 'Hệ thống sẽ bảo trì từ 2:00 - 4:00 sáng ngày mai',
                    'icon' => '📢',
                    'action_url' => null,
                    'extra_data' => [
                        'maintenance_time' => '2:00 - 4:00 AM'
                    ]
                ]
            ]
        ];

        foreach ($sampleNotifications as $index => $notificationData) {
            $notification = Notification::create([
                'user_id' => $notificationData['user_id'],
                'type' => $notificationData['type'],
                'data' => $notificationData['data'],
                'created_at' => now()->subHours(rand(1, 72)), // Random trong 3 ngày
                'updated_at' => now()->subHours(rand(1, 72))
            ]);

            // Random mark some as read
            if (fake()->boolean(60)) { // 60% chance đã đọc
                $notification->markAsRead();
            }

            $this->command->info("Tạo notification " . ($index + 1) . "/7: " . $notificationData['data']['title']);
        }

        $this->command->info('✅ Đã seed 7 notifications thành công!');
    }
}