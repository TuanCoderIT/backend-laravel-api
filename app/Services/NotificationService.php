<?php

namespace App\Services;

use App\Models\Notification;
use App\Events\NotificationCreated;

class NotificationService
{
    /**
     * Tạo notification mới
     */
    public function create(int $userId, string $type, array $data): Notification
    {
        $notification = Notification::create([
            'user_id' => $userId,
            'type' => $type,
            'data' => $data,
        ]);

        broadcast(new NotificationCreated($notification));

        return $notification;
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
                // 'data' => json_encode($data),
                'data' => json_encode($data, JSON_UNESCAPED_UNICODE),
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
     * Notification cho hoàn thành quiz
     */
    public function quizCompleted(int $userId, array $quizData): Notification
    {
        $score = (int) ($quizData['score'] ?? 0);
        $total = max(1, (int) ($quizData['total'] ?? 1));
        $percentage = (int) ($quizData['percentage'] ?? round(($score / $total) * 100));
        $passingScore = (int) ($quizData['passing_score'] ?? 70);
        $passed = $percentage >= $passingScore;
        $quizId = $quizData['quiz_id'];
        $quizTitle = $quizData['quiz_title'] ?? 'Quiz';

        return $this->create($userId, 'quiz_completed', [
            'title' => $passed ? 'Hoàn thành quiz' : 'Đã nộp bài quiz',
            'message' => "Bạn đạt {$percentage}% trong quiz \"{$quizTitle}\".",
            'icon' => $passed ? '🏆' : '📝',
            'action_url' => "/quiz/{$quizId}/result",
            'extra_data' => [
                'screen' => 'quiz_result',
                'quiz_id' => $quizId,
                'quiz_title' => $quizTitle,
                'result_id' => $quizData['result_id'] ?? null,
                'score' => $score,
                'total' => $total,
                'percentage' => $percentage,
                'passed' => $passed,
            ],
        ]);
    }

    /**
     * Notification cho điểm tốt nhất mới
     */
    public function quizBestScore(int $userId, array $quizData): Notification
    {
        $quizId = $quizData['quiz_id'];
        $quizTitle = $quizData['quiz_title'] ?? 'Quiz';
        $percentage = (int) ($quizData['percentage'] ?? 0);
        $previousBest = $quizData['previous_best'] ?? null;

        return $this->create($userId, 'quiz_best_score', [
            'title' => 'Kỷ lục quiz mới',
            'message' => "Bạn vừa đạt điểm tốt nhất mới {$percentage}% trong \"{$quizTitle}\".",
            'icon' => '🌟',
            'action_url' => "/quiz/{$quizId}/result",
            'extra_data' => [
                'screen' => 'quiz_result',
                'quiz_id' => $quizId,
                'quiz_title' => $quizTitle,
                'result_id' => $quizData['result_id'] ?? null,
                'score' => $quizData['score'] ?? null,
                'total' => $quizData['total'] ?? null,
                'percentage' => $percentage,
                'previous_best' => $previousBest,
            ],
        ]);
    }

    /**
     * Notification cho tạo bộ flashcard
     */
    public function flashcardSetCreated(int $userId, array $setData): Notification
    {
        $setId = $setData['flashcard_set_id'];
        $setTitle = $setData['flashcard_set_title'] ?? 'Bộ thẻ';

        return $this->create($userId, 'flashcard_set_created', [
            'title' => 'Đã tạo bộ thẻ',
            'message' => "Bộ thẻ \"{$setTitle}\" đã sẵn sàng để học.",
            'icon' => '🃏',
            'action_url' => "/card/{$setId}",
            'extra_data' => [
                'screen' => 'flashcard_set',
                'flashcard_set_id' => $setId,
                'flashcard_set_title' => $setTitle,
                'cards_count' => $setData['cards_count'] ?? null,
                'source_type' => $setData['source_type'] ?? null,
                'exam_id' => $setData['exam_id'] ?? null,
            ],
        ]);
    }

    /**
     * Notification cho hoàn thành cả bộ flashcard
     */
    public function flashcardCompleted(int $userId, array $setData): Notification
    {
        $setId = $setData['flashcard_set_id'];
        $setTitle = $setData['flashcard_set_title'] ?? 'Bộ thẻ';

        return $this->create($userId, 'flashcard_completed', [
            'title' => 'Hoàn thành bộ thẻ',
            'message' => "Bạn đã hoàn thành toàn bộ thẻ trong \"{$setTitle}\".",
            'icon' => '✅',
            'action_url' => "/card/{$setId}",
            'extra_data' => [
                'screen' => 'flashcard_set',
                'flashcard_set_id' => $setId,
                'flashcard_set_title' => $setTitle,
                'cards_count' => $setData['cards_count'] ?? null,
            ],
        ]);
    }

    /**
     * Notification cho AI quiz generation
     */
    public function aiQuizGenerated(int $userId, array $quizData): Notification
    {
        $quizId = $quizData['quiz_id'];
        $quizTitle = $quizData['quiz_title'] ?? 'Quiz AI';

        return $this->create($userId, 'ai_quiz_generated', [
            'title' => 'AI đã tạo quiz',
            'message' => "Quiz \"{$quizTitle}\" đã được tạo thành công.",
            'icon' => '🤖',
            'action_url' => "/quiz/{$quizId}",
            'extra_data' => [
                'screen' => 'quiz_detail',
                'quiz_id' => $quizId,
                'quiz_title' => $quizTitle,
                'questions_count' => $quizData['questions_count'] ?? null,
                'source' => $quizData['source'] ?? null,
            ],
        ]);
    }

    /**
     * Notification cho unlock achievement lần đầu
     */
    public function achievementUnlocked(int $userId, array $achievementData): Notification
    {
        $achievementName = $achievementData['achievement_name'] ?? 'Thành tựu';

        return $this->create($userId, 'achievement_unlocked', [
            'title' => 'Mở khóa thành tựu',
            'message' => "Bạn vừa mở khóa thành tựu \"{$achievementName}\".",
            'icon' => $achievementData['icon'] ?? '🏅',
            'action_url' => '/achievements',
            'extra_data' => [
                'screen' => 'achievements',
                'achievement_id' => $achievementData['achievement_id'] ?? null,
                'achievement_code' => $achievementData['achievement_code'] ?? null,
                'achievement_name' => $achievementName,
                'xp_reward' => $achievementData['xp_reward'] ?? 0,
            ],
        ]);
    }

    /**
     * Notification cho streak milestone
     */
    public function streakMilestone(int $userId, array $streakData): Notification
    {
        $days = (int) ($streakData['days'] ?? 0);

        return $this->create($userId, 'streak_milestone', [
            'title' => "{$days} ngày học liên tiếp",
            'message' => "Bạn đã duy trì chuỗi học {$days} ngày. Tiếp tục giữ nhịp nhé!",
            'icon' => '🔥',
            'action_url' => '/profile',
            'extra_data' => [
                'screen' => 'profile',
                'days' => $days,
                'milestone' => $days,
            ],
        ]);
    }

    /**
     * Notification cho tham gia nhóm mới
     */
    public function joinedGroup(int $userId, array $groupData): Notification
    {
        $groupId = $groupData['group_id'];
        $groupName = $groupData['group_name'] ?? 'Nhóm học tập';

        return $this->create($userId, 'joined_group', [
            'title' => 'Tham gia nhóm mới',
            'message' => "Bạn đã tham gia nhóm \"{$groupName}\".",
            'icon' => '👥',
            'action_url' => "/group/{$groupId}",
            'extra_data' => [
                'screen' => 'group',
                'group_id' => $groupId,
                'group_name' => $groupName,
                'group_slug' => $groupData['group_slug'] ?? null,
            ],
        ]);
    }

    /**
     * Notification cho lời mời vào nhóm
     */
    public function groupInviteReceived(int $userId, array $groupData): Notification
    {
        $groupId = $groupData['group_id'];
        $groupName = $groupData['group_name'] ?? 'Nhóm học tập';

        return $this->create($userId, 'group_invite_received', [
            'title' => 'Lời mời vào nhóm',
            'message' => "Bạn được mời tham gia nhóm \"{$groupName}\".",
            'icon' => '✉️',
            'action_url' => "/group/{$groupId}",
            'extra_data' => [
                'screen' => 'group',
                'group_id' => $groupId,
                'group_name' => $groupName,
                'group_slug' => $groupData['group_slug'] ?? null,
                'inviter_id' => $groupData['inviter_id'] ?? null,
                'inviter_name' => $groupData['inviter_name'] ?? null,
            ],
        ]);
    }

    /**
     * Notification cho khóa học mới
     */
    public function newCourse(array $userIds, array $courseData): int
    {
        return $this->createForUsers($userIds, 'new_course', [
            'title' => 'Khóa học mới',
            'message' => "Khóa học mới \"{$courseData['course_title']}\" đã được thêm.",
            'icon' => '📚',
            'action_url' => "/courses/{$courseData['course_slug']}",
            'extra_data' => [
                'screen' => 'course_detail',
                'course_id' => $courseData['course_id'],
                'course_title' => $courseData['course_title'],
                'course_slug' => $courseData['course_slug'],
            ],
        ]);
    }

    /**
     * Notification cho hoàn thành khóa học
     */
    public function courseCompleted(int $userId, array $courseData): Notification
    {
        return $this->create($userId, 'course_completed', [
            'title' => 'Hoàn thành khóa học',
            'message' => "Chúc mừng! Bạn đã hoàn thành khóa học \"{$courseData['course_title']}\".",
            'icon' => '🎉',
            'action_url' => "/courses/{$courseData['course_slug']}",
            'extra_data' => [
                'screen' => 'course_detail',
                'course_id' => $courseData['course_id'],
                'course_title' => $courseData['course_title'],
                'completion_rate' => $courseData['completion_rate'] ?? 100,
            ],
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
            'icon' => $announcementData['icon'] ?? '📢',
            'action_url' => $announcementData['action_url'] ?? null,
            'extra_data' => array_merge([
                'screen' => $announcementData['screen'] ?? 'notifications',
            ], $announcementData['extra_data'] ?? []),
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
