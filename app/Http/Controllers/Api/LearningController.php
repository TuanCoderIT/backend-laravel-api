<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\CourseLesson;
use App\Models\CourseProgress;
use App\Models\CourseUnlock;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LearningController extends Controller
{
    // ================== CẤU HÌNH NGƯỠNG ==================

    // % xem video để UNLOCK bài tiếp theo
    private const VIDEO_UNLOCK_PERCENT   = 50;

    // % xem video để đánh dấu COMPLETED
    private const VIDEO_COMPLETE_PERCENT = 90;

    // % scroll text để UNLOCK bài tiếp theo
    private const TEXT_UNLOCK_PERCENT    = 90;

    // % scroll text để COMPLETED
    private const TEXT_COMPLETE_PERCENT  = 100;

    /**
     * Xem chi tiết bài học + trạng thái unlock + tiến độ
     */
    public function showLesson(Request $request, Course $course, CourseLesson $lesson)
    {
        $user = $request->user();

        // Kiểm tra bài học thuộc khóa học
        if ($lesson->chapter->course_id !== $course->id) {
            return response()->json(['message' => 'Lesson not in course'], 404);
        }

        // Kiểm tra quyền xem
        $canView = $this->checkLessonAccess($user, $lesson, $course);

        // Lấy progress hoặc tạo mới
        $progress = CourseProgress::firstOrCreate([
            'user_id'   => $user->id,
            'lesson_id' => $lesson->id,
        ]);

        return response()->json([
            'lesson'          => $lesson,
            'can_view'        => $canView,
            'progress'        => $progress,
            'previous_lesson' => $this->findPreviousLesson($lesson),
            'next_lesson'     => $this->findNextLesson($lesson),
        ]);
    }

    /**
     * Cập nhật tiến độ học
     *
     * YÊU CẦU MỚI:
     * - Video: unlock bài tiếp theo khi xem >= 50% (không tính tua → frontend phải gửi watched_seconds đã lọc tua)
     * - Completed có thể để 90% (hoặc cao hơn tùy bạn)
     * - Text: dùng scroll_percent như cũ nhưng tách ngưỡng unlock/completed
     */
    public function updateProgress(Request $request, CourseLesson $lesson)
    {
        $request->validate([
            'watched_seconds' => 'nullable|integer|min:0',
            'percentage'      => 'nullable|integer|min:0|max:100', // giữ lại cho tương thích nhưng KHÔNG dùng để tính
            'scroll_percent'  => 'nullable|integer|min:0|max:100',
        ]);

        $user = Auth::user();

        $progress = CourseProgress::firstOrCreate([
            'user_id'   => $user->id,
            'lesson_id' => $lesson->id,
        ]);

        $originalWatched = (int)$progress->watched_seconds;
        $originalScroll  = (int)$progress->scroll_percent;

        // ==================================================
        // 1. VIDEO: Cập nhật watched_seconds (KHÔNG tính tua)
        // ==================================================
        if ($lesson->type === 'video' && $request->filled('watched_seconds')) {

            $incoming = (int)$request->input('watched_seconds');

            // Clamp theo duration để tránh frontend bug gửi quá dài
            if ($lesson->duration_seconds > 0) {
                $incoming = min($incoming, (int)$lesson->duration_seconds);
            }

            // Monotonic: không bao giờ cho phép giảm
            if ($incoming > $originalWatched) {
                $progress->watched_seconds = $incoming;
            }
        }

        // NOTE: KHÔNG dùng $request->percentage để tăng watched_seconds nữa,
        // vì percentage thường tính từ currentTime (dính tua).
        // Nếu muốn dùng, frontend phải gửi percentage dựa trên maxWatched chứ không phải currentTime.

        // ==================================================
        // 2. TEXT: Cập nhật scroll_percent
        // ==================================================
        if ($lesson->type === 'text' && $request->filled('scroll_percent')) {

            $incomingScroll = (int)$request->input('scroll_percent');

            // Clamp trong [0..100]
            $incomingScroll = max(0, min(100, $incomingScroll));

            // Monotonic
            if ($incomingScroll > $originalScroll) {
                $progress->scroll_percent = $incomingScroll;
            }
        }

        // ==================================================
        // 3. Kiểm tra UNLOCK + COMPLETED
        // ==================================================

        $nextLesson = null;

        // 3.1. VIDEO
        if ($lesson->type === 'video' && $lesson->duration_seconds && $lesson->duration_seconds >= 30) {

            $duration = (int)$lesson->duration_seconds;
            $watched  = (int)$progress->watched_seconds;

            $unlockAt   = (int)ceil($duration * (self::VIDEO_UNLOCK_PERCENT / 100));
            $completeAt = (int)ceil($duration * (self::VIDEO_COMPLETE_PERCENT / 100));

            // 3.1.1. Unlock bài tiếp theo khi đạt 50%
            if ($watched >= $unlockAt) {
                // firstOrCreate bên dưới đã idempotent → gọi nhiều lần cũng không sao
                $nextLesson = $this->unlockNextLesson($user->id, $lesson);
            }

            // 3.1.2. Đánh dấu completed khi đạt 90%
            if (!$progress->is_completed && $watched >= $completeAt) {
                $progress->is_completed = true;
            }
        }

        // 3.2. TEXT
        if ($lesson->type === 'text') {
            $scroll = (int)$progress->scroll_percent;

            // Unlock bài tiếp theo khi scroll đủ
            if ($scroll >= self::TEXT_UNLOCK_PERCENT) {
                $nextLesson = $this->unlockNextLesson($user->id, $lesson);
            }

            // Completed khi đủ ngưỡng (100% hoặc 90% tùy bạn chọn ở trên)
            if (!$progress->is_completed && $scroll >= self::TEXT_COMPLETE_PERCENT) {
                $progress->is_completed = true;
            }
        }

        $progress->save();

        return response()->json([
            'message'       => 'Progress updated',
            'is_completed'  => (bool)$progress->is_completed,
            'next_lesson'   => $nextLesson,
            'watched'       => (int)$progress->watched_seconds,
            'scroll'        => (int)$progress->scroll_percent,
        ]);
    }

    /**
     * Resume lesson (lấy lại tiến độ đã lưu)
     */
    public function resumeLesson(CourseLesson $lesson)
    {
        $user = Auth::user();

        $progress = CourseProgress::firstOrCreate([
            'user_id'   => $user->id,
            'lesson_id' => $lesson->id,
        ]);

        return response()->json([
            'lesson_id'       => $lesson->id,
            'watched_seconds' => (int)$progress->watched_seconds,
            'scroll_percent'  => (int)$progress->scroll_percent,
            'is_completed'    => (bool)$progress->is_completed,
        ]);
    }

    /**
     * Học 1 bài (đánh dấu completed + unlock next) – giữ nguyên
     */
    public function learn(Course $course, CourseLesson $lesson)
    {
        $user = Auth::user();

        if ($lesson->chapter->course_id !== $course->id) {
            return response()->json(['message' => 'Lesson not in course'], 404);
        }

        $progress = CourseProgress::firstOrCreate([
            'user_id'   => $user->id,
            'lesson_id' => $lesson->id,
        ]);

        $progress->is_completed = true;

        if ($lesson->type === 'video') {
            $progress->watched_seconds = $lesson->duration_seconds;
        } elseif ($lesson->type === 'text') {
            $progress->scroll_percent = 100;
        }

        $progress->save();

        $nextLesson = $this->unlockNextLesson($user->id, $lesson);

        return response()->json([
            'message'     => 'Lesson marked as learned',
            'progress'    => $progress,
            'next_lesson' => $nextLesson,
        ]);
    }

    /**
     * Tiến độ toàn khóa học (%)
     */
    public function courseProgress(Course $course)
    {
        $user = Auth::user();

        $totalLessons = $course->chapters()->withCount('lessons')->get()->sum('lessons_count');

        $completedLessons = CourseProgress::whereHas('lesson.chapter', function ($q) use ($course) {
            $q->where('course_id', $course->id);
        })->where('user_id', $user->id)
            ->where('is_completed', true)
            ->count();

        $progressPercent = $totalLessons > 0
            ? round(($completedLessons / $totalLessons) * 100, 2)
            : 0;

        return response()->json([
            'total_lessons'     => $totalLessons,
            'completed_lessons' => $completedLessons,
            'progress_percent'  => $progressPercent,
        ]);
    }

    /**
     * Check quyền xem bài học (public / preview / unlocked)
     */
    private function checkLessonAccess($user, CourseLesson $lesson, Course $course): bool
    {
        if ($course->is_public) return true;
        if ($lesson->is_preview) return true;

        return CourseUnlock::where([
            'user_id'   => $user->id,
            'lesson_id' => $lesson->id,
        ])->exists();
    }

    /**
     * Unlock bài tiếp theo
     */
    private function unlockNextLesson(int $userId, CourseLesson $lesson)
    {
        $next = $this->findNextLesson($lesson);
        if (!$next) return null;

        CourseUnlock::firstOrCreate(
            ['user_id' => $userId, 'lesson_id' => $next->id],
            ['unlocked_at' => now()]
        );

        return $next;
    }

    /**
     * Tìm bài tiếp theo
     */
    private function findNextLesson(CourseLesson $lesson)
    {
        $next = CourseLesson::where('chapter_id', $lesson->chapter_id)
            ->where('order', '>', $lesson->order)
            ->orderBy('order')
            ->first();

        if ($next) return $next;

        $nextChapter = $lesson->chapter->course->chapters()
            ->where('order', '>', $lesson->chapter->order)
            ->orderBy('order')
            ->first();

        return $nextChapter?->lessons()->orderBy('order')->first();
    }

    /**
     * Tìm bài trước
     */
    private function findPreviousLesson(CourseLesson $lesson)
    {
        $prev = CourseLesson::where('chapter_id', $lesson->chapter_id)
            ->where('order', '<', $lesson->order)
            ->orderBy('order', 'desc')
            ->first();

        if ($prev) return $prev;

        $prevChapter = $lesson->chapter->course->chapters()
            ->where('order', '<', $lesson->chapter->order)
            ->orderBy('order', 'desc')
            ->first();

        return $prevChapter?->lessons()->orderBy('order', 'desc')->first();
    }

    /**
     * Lấy bài tiếp theo (API riêng)
     */
    public function nextLesson(CourseLesson $lesson)
    {
        return response()->json($this->findNextLesson($lesson));
    }
}
