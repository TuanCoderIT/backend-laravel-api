<?php

namespace App\Services;

use App\Models\Course;
use App\Models\CourseLesson;
use App\Models\CourseProgress;
use App\Models\CourseUnlock;

class LessonService
{
    public static function checkLessonBelongsToCourse(CourseLesson $lesson, Course $course): bool
    {
        return optional($lesson->chapter)->course_id === $course->id;
    }

    public static function checkLessonBelongsToChapter(CourseLesson $lesson, int $chapterId): bool
    {
        return $lesson->chapter_id === $chapterId;
    }

    public static function getProgress(int $userId, CourseLesson $lesson): CourseProgress
    {
        return CourseProgress::firstOrCreate([
            'user_id'   => $userId,
            'lesson_id' => $lesson->id,
        ]);
    }

    public static function isCompleted(CourseLesson $lesson, CourseProgress $progress): bool
    {
        return match ($lesson->type) {
            'video' => ($lesson->duration_seconds > 0) &&
                       ($progress->watched_seconds >= ($lesson->duration_seconds * 0.5)),
            'text'  => $progress->scroll_percent >= 50,
            default => false,
        };
    }

    public static function unlockNextLesson(int $userId, CourseLesson $lesson): ?CourseLesson
    {
        $next = self::findNextLesson($lesson);
        if (!$next) return null;

        CourseUnlock::firstOrCreate(
            ['user_id' => $userId, 'lesson_id' => $next->id],
            ['unlocked_at' => now()]
        );

        return $next;
    }

    public static function findNextLesson(CourseLesson $lesson): ?CourseLesson
    {
        // Next in chapter
        $next = CourseLesson::where('chapter_id', $lesson->chapter_id)
            ->where('order', '>', $lesson->order)
            ->orderBy('order')
            ->first();

        if ($next) return $next;

        // Next chapter → first lesson
        $nextChapter = optional($lesson->chapter->course)
            ->chapters()
            ->where('order', '>', $lesson->chapter->order)
            ->orderBy('order')
            ->first();

        return $nextChapter?->lessons()->orderBy('order')->first();
    }

    public static function findPreviousLesson(CourseLesson $lesson): ?CourseLesson
    {
        $prev = CourseLesson::where('chapter_id', $lesson->chapter_id)
            ->where('order', '<', $lesson->order)
            ->orderBy('order', 'desc')
            ->first();

        if ($prev) return $prev;

        $prevChapter = optional($lesson->chapter->course)
            ->chapters()
            ->where('order', '<', $lesson->chapter->order)
            ->orderBy('order', 'desc')
            ->first();

        return $prevChapter?->lessons()->orderBy('order', 'desc')->first();
    }
}