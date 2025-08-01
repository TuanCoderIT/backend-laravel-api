<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Rating;

class RatingController extends Controller
{
    // Danh sách các model được phép đánh giá
    protected $modelMap = [
        'quiz' => \App\Models\Exam::class,

    ];
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $data = $request->validate([
            'type' => 'required|string|in:quiz,post,news',
            'id' => 'required|integer',
        ]);

        $modelClass = $this->modelMap[$data['type']] ?? null;
        if (!$modelClass) {
            return response()->json(['message' => 'Invalid type'], 400);
        }

        $rateable = $modelClass::findOrFail($data['id']);

        // Lấy danh sách đánh giá (có thể phân trang)
        $ratings = $rateable->ratings()->with('user')->latest()->paginate(10);

        return response()->json($ratings);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'type' => 'required|string|in:quiz',
            'id' => 'required|integer',
            'stars' => 'required|integer|min:1|max:5',
            'comment' => 'nullable|string',
        ]);

        $user = $request->user();
        $modelClass = $this->modelMap[$data['type']] ?? null;
        if (!$modelClass) {
            return response()->json(['message' => 'Invalid type'], 400);
        }

        $rateable = $modelClass::findOrFail($data['id']);

        // Kiểm tra nếu đã tồn tại đánh giá
        $existing = $rateable->ratings()->where('user_id', $user->id)->first();
        if ($existing) {
            return response()->json(['message' => 'Bạn đã đánh giá rồi.'], 409);
        }

        // Tạo mới
        $rating = $rateable->ratings()->create([
            'user_id' => $user->id,
            'stars' => $data['stars'],
            'comment' => $data['comment'],
        ]);

        return response()->json($rating, 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Rating $rating)
    {
        // $this->authorize('update', $rating); // Optional: nếu bạn có policy

        $data = $request->validate([
            'stars' => 'required|integer|min:1|max:5',
            'comment' => 'nullable|string',
        ]);

        $rating->update($data);

        return response()->json($rating);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Rating $rating)
    {
        // $this->authorize('delete', $rating); // Optional: nếu bạn có policy

        $rating->delete();

        return response()->noContent();
    }

    // Xem đánh giá của chính người dùng
    public function myRatings(Request $request)
    {
        $data = $request->validate([
            'type' => 'required|string|in:quiz,post,news',
            'id' => 'required|integer',
        ]);

        $user = $request->user();
        $modelClass = $this->modelMap[$data['type']] ?? null;
        if (!$modelClass) {
            return response()->json(['message' => 'Invalid type'], 400);
        }

        $rateable = $modelClass::findOrFail($data['id']);

        $rating = $rateable->ratings()
            ->where('user_id', $user->id)
            ->first();

        return response()->json($rating);
    }

    // Thống kê đánh giá của người dùng
    public function stats(Request $request)
    {
        $data = $request->validate([
            'type' => 'required|string|in:quiz,post,news',
            'id' => 'required|integer',
        ]);

        $modelClass = $this->modelMap[$data['type']] ?? null;
        if (!$modelClass) {
            return response()->json(['message' => 'Invalid type'], 400);
        }

        $rateable = $modelClass::findOrFail($data['id']);
        $ratings = $rateable->ratings()->get();

        $total = $ratings->count();
        $average = round($ratings->avg('stars'), 1);

        $distribution = [1 => 0, 2 => 0, 3 => 0, 4 => 0, 5 => 0];
        foreach ($ratings as $rating) {
            $distribution[$rating->stars]++;
        }

        return response()->json([
            'average_rating' => $average,
            'total_ratings' => $total,
            'rating_distribution' => $distribution,
        ]);
    }
}
