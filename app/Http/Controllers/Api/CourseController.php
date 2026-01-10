<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\CourseStoreRequest;
use App\Http\Requests\Admin\CourseUpdateRequest;
use App\Http\Resources\CourseResource;
use App\Models\Course;
use App\Models\TokenPricing;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\Request;

class CourseController extends Controller
{
    public function index(Request $request)
    {
        $query = Course::with('user', 'category', 'tokenPricing')
            ->withCount('chapters')
            ->when($request->has('search'), function ($q) use ($request) {
                $q->where('title', 'like', '%' . $request->search . '%');
            })
            ->orderByDesc('created_at');

        return CourseResource::collection($query->get());
    }

    public function store(CourseStoreRequest $request)
    {
        $data = $request->validated();

        $priceTokens = $data['price_token'] ?? 0;

        unset($data['price_token']);
        $data['user_id'] = $request->user()->id;

        if ($request->hasFile('thumbnail')) {
            $file = $request->file('thumbnail');
            $path = $file->store('thumbnails', 'public');

            $data['thumbnail'] = '/storage/' . $path;
        } else {
            $data['thumbnail'] = null;
        }

        $course = Course::create($data);
        
        TokenPricing::updateOrCreate(
            [
                'target_type' => Course::class,
                'target_id'   => $course->id,
            ],
            [
                'price_token' => $priceTokens,
            ]
        );

        return new CourseResource($course->load('user', 'category', 'tokenPricing'));
    }

    public function show(Course $course)
    {
        $course->load('user', 'category', 'chapters.lessons', 'tokenPricing');
        return new CourseResource($course);
    }

    public function showBySlug($slug)
    {
        $course = Course::with('user', 'category', 'chapters.lessons', 'tokenPricing')
            ->where('slug', $slug)
            ->firstOrFail();
        return new CourseResource($course);
    }

    public function update(CourseUpdateRequest $request, Course $course)
    {
        $data = $request->validated();

        $priceTokens = $data['price_token'] ?? null;
        unset($data['price_token']);

        if ($request->hasFile('thumbnail')) {

            if ($course->thumbnail && str_starts_with($course->thumbnail, '/storage/')) {
                $oldPath = str_replace('/storage/', '', $course->thumbnail);
                Storage::disk('public')->delete($oldPath);
            }

            $file = $request->file('thumbnail');
            $path = $file->store('thumbnails', 'public');
            $data['thumbnail'] = '/storage/' . $path;
        }

        $course->update($data);

        if (isset($priceTokens)) {
            TokenPricing::updateOrCreate(
                [
                    'target_type' => Course::class,
                    'target_id'   => $course->id,
                ],
                [
                    'price_token' => $priceTokens,
                ]
            );
        }
        $course->load('tokenPricing');

        return new CourseResource($course->load('user', 'category'));
    }

    public function destroy(Course $course)
    {
        // Khuyến nghị: Bật Policy để kiểm tra quyền
        // $this->authorize('delete', $course); 

        // Xóa Token Pricing liên quan (nếu không dùng Model Events)
        $course->tokenPricing()->delete();

        // Xóa Khóa học (sẽ tự động xóa Chapters/Lessons nếu đã cấu hình onDelete('cascade'))
        $course->delete();

        return response()->json(['message' => 'Course deleted successfully']);
    }
}
