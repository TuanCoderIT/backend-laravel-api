<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\FlashcardSetResource;
use App\Models\FlashcardSet;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class FlashcardModerationController extends Controller
{
    /**
     * GET /api/admin/flashcard-sets/pending
     * Deprecated: flashcard sets no longer require moderation.
     */
    public function pending(Request $request): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => 'Flashcard moderation đã deprecated. Bộ thẻ public không cần admin duyệt.',
        ], 410);
    }

    /**
     * POST /api/admin/flashcard-sets/{flashcardSet}/approve
     * Deprecated: flashcard sets no longer require moderation.
     */
    public function approve(Request $request, FlashcardSet $flashcardSet): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => 'Flashcard approve đã deprecated. Bộ thẻ public không cần admin duyệt.',
        ], 410);
    }

    /**
     * POST /api/admin/flashcard-sets/{flashcardSet}/reject
     * Deprecated: flashcard sets no longer require moderation.
     */
    public function reject(Request $request, FlashcardSet $flashcardSet): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => 'Flashcard reject đã deprecated. Bộ thẻ public không cần admin duyệt.',
        ], 410);
    }

    /**
     * POST /api/admin/flashcard-sets/{flashcardSet}/archive
     * Lưu trữ bộ thẻ.
     */
    public function archive(Request $request, FlashcardSet $flashcardSet): JsonResponse
    {
        $flashcardSet->update([
            'status' => 'archived',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Đã lưu trữ bộ thẻ.',
            'data' => new FlashcardSetResource(
                $flashcardSet->fresh()
                    ->load(['user:id,name', 'category:id,name'])
                    ->loadCount('flashcards')
            ),
        ]);
    }
}
