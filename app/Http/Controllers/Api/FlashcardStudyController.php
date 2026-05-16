<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Flashcard;
use App\Models\FlashcardSet;
use App\Models\FlashcardProgress;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class FlashcardStudyController extends Controller
{
    /**
     * GET /api/flashcard-sets/{flashcardSet}/study
     * Lấy dữ liệu học.
     */
    public function study(Request $request, FlashcardSet $flashcardSet): JsonResponse
    {
        if (
            $flashcardSet->status !== 'published' &&
            $flashcardSet->user_id !== $request->user()->id
        ) {
            return response()->json([
                'success' => false,
                'message' => 'Bạn không có quyền truy cập bộ thẻ này.',
            ], 403);
        }

        $flashcardSet->load([
            'flashcards.progress' => function ($query) use ($request) {
                $query->where('user_id', $request->user()->id);
            },
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Lấy dữ liệu học thành công.',
            'data'    => $flashcardSet,
        ]);
    }

    /**
     * POST /api/flashcards/{flashcard}/review
     * Cập nhật tiến độ học.
     */
    public function review(Request $request, Flashcard $flashcard): JsonResponse
    {
        $validated = $request->validate([
            'rating' => ['required', 'in:again,hard,easy'],
        ]);

        $progress = FlashcardProgress::firstOrCreate(
            [
                'user_id' => $request->user()->id,
                'flashcard_id' => $flashcard->id,
            ],
            [
                'status' => 'new',
                'correct_count' => 0,
                'review_count' => 0,
            ]
        );

        $progress->review_count += 1;
        $progress->last_reviewed_at = now();

        switch ($validated['rating']) {
            case 'again':
                $progress->status = 'learning';
                $progress->next_review_at = now()->addHours(1);
                break;

            case 'hard':
                $progress->status = 'learning';
                $progress->correct_count += 1;
                $progress->next_review_at = now()->addDays(1);
                break;

            case 'easy':
                $progress->status = 'mastered';
                $progress->correct_count += 1;
                $progress->next_review_at = now()->addDays(7);
                break;
        }

        $progress->save();

        return response()->json([
            'success' => true,
            'message' => 'Cập nhật tiến độ học thành công.',
            'data'    => $progress,
        ]);
    }
}