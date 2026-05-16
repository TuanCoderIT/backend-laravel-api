<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\FlashcardSet;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class FlashcardModerationController extends Controller
{
    /**
     * GET /api/admin/flashcard-sets/pending
     * Danh sách bộ thẻ chờ duyệt.
     */
    public function pending(Request $request): JsonResponse
    {
        // TODO: Thêm middleware kiểm tra admin.
        $flashcardSets = FlashcardSet::with([
            'user:id,name',
            'quiz:id,title',
        ])
            ->withCount('flashcards')
            ->where('status', 'pending')
            ->latest('submitted_at')
            ->paginate(20);

        return response()->json([
            'success' => true,
            'message' => 'Lấy danh sách bộ thẻ chờ duyệt thành công.',
            'data' => $flashcardSets,
        ]);
    }

    /**
     * POST /api/admin/flashcard-sets/{flashcardSet}/approve
     * Duyệt bộ thẻ.
     */
    public function approve(Request $request, FlashcardSet $flashcardSet): JsonResponse
    {
        // TODO: Thêm middleware kiểm tra admin.

        if ($flashcardSet->status !== 'pending') {
            return response()->json([
                'success' => false,
                'message' => 'Chỉ có thể duyệt bộ thẻ đang ở trạng thái pending.',
            ], 422);
        }

        $flashcardSet->update([
            'status' => 'published',
            'reviewed_by' => $request->user()->id,
            'reviewed_at' => now(),
            'review_notes' => $request->input('review_notes'),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Duyệt bộ thẻ thành công.',
            'data' => $flashcardSet->fresh()
                ->load(['user:id,name', 'reviewer:id,name'])
                ->loadCount('flashcards'),
        ]);
    }

    /**
     * POST /api/admin/flashcard-sets/{flashcardSet}/reject
     * Từ chối bộ thẻ.
     */
    public function reject(Request $request, FlashcardSet $flashcardSet): JsonResponse
    {
        // TODO: Thêm middleware kiểm tra admin.

        if ($flashcardSet->status !== 'pending') {
            return response()->json([
                'success' => false,
                'message' => 'Chỉ có thể từ chối bộ thẻ đang ở trạng thái pending.',
            ], 422);
        }

        $validated = $request->validate([
            'review_notes' => ['required', 'string'],
        ]);

        $flashcardSet->update([
            'status' => 'rejected',
            'reviewed_by' => $request->user()->id,
            'reviewed_at' => now(),
            'review_notes' => $validated['review_notes'],
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Đã từ chối bộ thẻ.',
            'data' => $flashcardSet->fresh()
                ->load(['user:id,name', 'reviewer:id,name'])
                ->loadCount('flashcards'),
        ]);
    }

    /**
     * POST /api/admin/flashcard-sets/{flashcardSet}/archive
     * Lưu trữ bộ thẻ.
     */
    public function archive(Request $request, FlashcardSet $flashcardSet): JsonResponse
    {
        // TODO: Thêm middleware kiểm tra admin.

        $flashcardSet->update([
            'status' => 'archived',
            'reviewed_by' => $request->user()->id,
            'reviewed_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Đã lưu trữ bộ thẻ.',
            'data' => $flashcardSet->fresh()
                ->load(['user:id,name', 'reviewer:id,name'])
                ->loadCount('flashcards'),
        ]);
    }
}
