<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\LessonStoreRequest;
use App\Http\Requests\Admin\LessonUpdateRequest;
use App\Http\Resources\LessonResource;
use App\Models\CourseLesson;
use App\Models\CourseChapter;

class CourseLessonController extends Controller
{
    public function index(CourseChapter $chapter)
    {
        $lessons = $chapter->lessons()->orderBy('order')->get();
        return LessonResource::collection($lessons);
    }

    public function store(LessonStoreRequest $request, CourseChapter $chapter)
    {
        $lesson = $chapter->lessons()->create($request->validated());
        return new LessonResource($lesson);
    }

    public function show(CourseChapter $chapter, CourseLesson $lesson)
    {
        if ($lesson->chapter_id !== $chapter->id) {
            abort(404, 'Lesson not found in this chapter');
        }

        return new LessonResource($lesson->load('chapter.course'));
    }

    public function update(LessonUpdateRequest $request, CourseChapter $chapter, CourseLesson $lesson)
    {
        if ($lesson->chapter_id !== $chapter->id) {
            abort(404, 'Lesson not found in this chapter');
        }

        $lesson->update($request->validated());
        return new LessonResource($lesson);
    }

    public function destroy(CourseChapter $chapter, CourseLesson $lesson)
    {
        if ($lesson->chapter_id !== $chapter->id) {
            abort(404, 'Lesson not found in this chapter');
        }

        $lesson->delete();

        return response()->json(['message' => 'Lesson deleted successfully']);
    }
}