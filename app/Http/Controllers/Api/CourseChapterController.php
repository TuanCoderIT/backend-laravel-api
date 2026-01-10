<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ChapterStoreRequest;
use App\Http\Requests\Admin\ChapterUpdateRequest;
use App\Http\Resources\ChapterResource;
use App\Models\CourseChapter;
use App\Models\Course;

class CourseChapterController extends Controller
{
    public function index(Course $course)
    {
        $chapters = $course->chapters()->get();
        return ChapterResource::collection($chapters);
    }

    public function store(ChapterStoreRequest $request, Course $course)
    {
        $chapter = $course->chapters()->create($request->validated());
        return new ChapterResource($chapter->load('lessons'));
    }

    public function show($id)
    {
        $chapter = CourseChapter::with('lessons')->findOrFail($id);
        return new ChapterResource($chapter);
    }

    public function update(ChapterUpdateRequest $request, Course $course, CourseChapter $chapter)
    {
        if ($chapter->course_id !== $course->id) {
            abort(404, "Chapter not found in this course");
        }
        $chapter->update($request->validated());
        return new ChapterResource($chapter->load('lessons'));
    }

    public function destroy(Course $course, CourseChapter $chapter)
    {
        if ($chapter->course_id !== $course->id) {
            abort(404, "Chapter not found in this course");
        }
        $chapter->delete();
        return response()->json(['message' => 'Chapter deleted successfully']);
    }
}
