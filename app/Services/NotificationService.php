<?php

namespace App\Services;

use App\Models\Notification;
use App\Models\User;

class NotificationService
{
    /**
     * Tạo notification mới
     */
    public function create(int $userId, string $type, array $data): Notification
    {
        return Notification::create([
            'user_id' => $userId,
            'type' => $type,
            'data' => $data
        ]);
    }

    /**
     * Tạo notification cho nhiều users
     */
    public function createForUsers(array $userIds, string $type, array $data): int
    {
        $notifications = [];
        $now = now();

        foreach ($userIds as $userId) {
            $notifications[] = [
                'user_id' => $userId,
                'type' => $type,
                'data' => json_encode($data),
                'created_at' => $now,
                'updated_at' => $now
            ];
        }

        Notification::insert($notifications);
        return count($notifications);
    }

    /**
     * Notification cho tin nhắn mới
     */
    public function newMessage(int $userId, array $messageData): Notification
    {
        return $this->create($userId, 'new_message', [
            'title' => 'Tin nhắn mới',
            'message' => "Bạn có tin nhắn mới từ {$messageData['sender_name']}",
            'icon' => '💬',
            'action_url' => "/chat/threads/{$messageData['thread_id']}",
            'extra_data' => [
                'thread_id' => $messageData['thread_id'],
                'sender_id' => $messageData['sender_id'],
                'sender_name' => $messageData['sender_name']
            ]
        ]);
    }

    /**
     * Notification cho group mới
     */
    public function joinedGroup(int $userId, array $groupData): Notification
    {
        return $this->create($userId, 'joined_group', [
            'title' => 'Tham gia nhóm mới',
            'message' => "Bạn đã tham gia nhóm {$groupData['group_name']}",
            'icon' => '👥',
            'action_url' => "/groups/{$groupData['group_slug']}",
            'extra_data' => [
                'group_id' => $groupData['group_id'],
                'group_name' => $groupData['group_name'],
                'group_slug' => $groupData['group_slug']
            ]
        ]);
    }

    /**
     * Notification cho khóa học mới
     */
    public function newCourse(array $userIds, array $courseData): int
    {
        return $this->createForUsers($userIds, 'new_course', [
            'title' => 'Khóa học mới',
            'message' => "Khóa học mới '{$courseData['course_title']}' đã được thêm",
            'icon' => '📚',
            'action_url' => "/courses/{$courseData['course_slug']}",
            'extra_data' => [
                'course_id' => $courseData['course_id'],
                'course_title' => $courseData['course_title'],
                'course_slug' => $courseData['course_slug']
            ]
        ]);
    }

    /**
     * Notification cho hoàn thành khóa học
     */
    public function courseCompleted(int $userId, array $courseData): Notification
    {
        return $this->create($userId, 'course_completed', [
            'title' => 'Hoàn thành khóa học',
            'message' => "Chúc mừng! Bạn đã hoàn thành khóa học '{$courseData['course_title']}'",
            'icon' => '🎉',
            'action_url' => "/courses/{$courseData['course_slug']}",
            'extra_data' => [
                'course_id' => $courseData['course_id'],
                'course_title' => $courseData['course_title'],
                'completion_rate' => $courseData['completion_rate'] ?? 100
            ]
        ]);
    }

    /**
     * Notification cho quiz completion
     */
    public function quizCompleted(int $userId, array $quizData): Notification
    {
        $passed = $quizData['score'] >= $quizData['passing_score'];
        
        return $this->create($userId, 'quiz_completed', [
            'title' => $passed ? 'Đạt điểm quiz' : 'Hoàn thành quiz',
            'message' => $passed 
                ? "Chúc mừng! Bạn đã đạt {$quizData['score']}% trong quiz '{$quizData['quiz_title']}'"
                : "Bạn đã hoàn thành quiz '{$quizData['quiz_title']}' với {$quizData['score']}%",
            'icon' => $passed ? '🏆' : '📝',
            'action_url' => "/exams/{$quizData['quiz_id']}/results",
            'extra_data' => [
                'quiz_id' => $quizData['quiz_id'],
                'quiz_title' => $quizData['quiz_title'],
                'score' => $quizData['score'],
                'passed' => $passed
            ]
        ]);
    }

    /**
     * Notification cho AI quiz generation
     */
    public function aiQuizGenerated(int $userId, array $quizData): Notification
    {
        return $this->create($userId, 'ai_quiz_generated', [
            'title' => 'AI tạo quiz thành công',
            'message' => "Quiz '{$quizData['quiz_title']}' đã được tạo từ file của bạn",
            'icon' => '🤖',
            'action_url' => "/exams/{$quizData['quiz_id']}",
            'extra_data' => [
                'quiz_id' => $quizData['quiz_id'],
                'quiz_title' => $quizData['quiz_title'],
                'questions_count' => $quizData['questions_count']
            ]
        ]);
    }

    /**
     * Notification cho token reward
     */
    public function tokenReward(int $userId, array $rewardData): Notification
    {
        return $this->create($userId, 'token_reward', [
            'title' => 'Nhận thưởng token',
            'message' => "Bạn đã nhận {$rewardData['amount']} tokens từ {$rewardData['reason']}",
            'icon' => '🪙',
            'action_url' => "/wallet",
            'extra_data' => [
                'amount' => $rewardData['amount'],
                'reason' => $rewardData['reason'],
                'transaction_id' => $rewardData['transaction_id'] ?? null
            ]
        ]);
    }

    /**
     * Notification cho system announcement
     */
    public function systemAnnouncement(array $userIds, array $announcementData): int
    {
        return $this->createForUsers($userIds, 'system_announcement', [
            'title' => $announcementData['title'],
            'message' => $announcementData['message'],
            'icon' => '📢',
            'action_url' => $announcementData['action_url'] ?? null,
            'extra_data' => $announcementData['extra_data'] ?? []
        ]);
    }

    /**
     * Xóa notifications cũ (cleanup)
     */
    public function cleanup(int $daysOld = 30): int
    {
        return Notification::where('created_at', '<', now()->subDays($daysOld))
            ->whereNotNull('read_at') // Chỉ xóa những cái đã đọc
            ->delete();
    }
}