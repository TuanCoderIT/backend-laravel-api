<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Course;
use App\Models\CourseChapter;
use App\Models\CourseLesson;

class ChapterLessonSeeder extends Seeder
{
    public function run(): void
    {
        // Lấy 10 khóa học đầu tiên
        $courses = Course::orderBy('id')->take(10)->get();

        foreach ($courses as $course) {
            // Tạo 5 chương cho mỗi khóa học
            $chapters = CourseChapter::factory()
                ->count(5)
                ->make()
                ->each(function ($chapter, $index) use ($course) {
                    $chapter->course_id = $course->id;
                    $chapter->order = $index + 1;
                    $chapter->save();

                    // Tạo 5 bài học cho mỗi chương
                    CourseLesson::factory()
                        ->count(5)
                        ->make()
                        ->each(function ($lesson, $i) use ($chapter) {
                            $lesson->chapter_id = $chapter->id;
                            $lesson->order = $i + 1;
                            $lesson->save();
                        });
                });
        }
    }
}
