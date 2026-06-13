<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\FlashcardResource;
use App\Models\Flashcard;
use App\Models\FlashcardSet;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class FlashcardController extends Controller
{
    /**
     * POST /api/flashcard-sets/{flashcardSet}/cards
     * Thêm thẻ mới vào bộ thẻ.
     */
    public function store(Request $request, FlashcardSet $flashcardSet): JsonResponse
    {
        if ($flashcardSet->user_id !== $request->user()->id) {
            return response()->json([
                'success' => false,
                'message' => 'Bạn không có quyền thêm thẻ vào bộ này.',
            ], 403);
        }

        $validated = $request->validate([
            'term' => ['required', 'string', 'max:1000'],
            'definition' => ['required', 'string', 'max:2000'],
            'explanation' => ['nullable', 'string', 'max:3000'],
        ]);

        $flashcard = $flashcardSet->flashcards()->create([
            'front_text' => $validated['term'],
            'back_text' => $validated['definition'],
            'explanation' => $validated['explanation'] ?? null,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Thêm flashcard thành công.',
            'data' => new FlashcardResource($flashcard),
        ], 201);
    }

    /**
     * PUT /api/flashcards/{flashcard}
     * Cập nhật nội dung flashcard.
     */
    public function update(Request $request, Flashcard $flashcard): JsonResponse
    {
        $flashcardSet = $flashcard->flashcardSet;

        if ($flashcardSet->user_id !== $request->user()->id) {
            return response()->json([
                'success' => false,
                'message' => 'Bạn không có quyền chỉnh sửa flashcard này.',
            ], 403);
        }

        $validated = $request->validate([
            'term' => ['sometimes', 'required', 'string', 'max:1000'],
            'definition' => ['sometimes', 'required', 'string', 'max:2000'],
            'explanation' => ['nullable', 'string', 'max:3000'],
        ]);

        $flashcard->update([
            'front_text' => $validated['term'] ?? $flashcard->front_text,
            'back_text' => $validated['definition'] ?? $flashcard->back_text,
            'explanation' => array_key_exists('explanation', $validated)
                ? $validated['explanation']
                : $flashcard->explanation,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Cập nhật flashcard thành công.',
            'data' => new FlashcardResource($flashcard->fresh()),
        ]);
    }

    /**
     * DELETE /api/flashcards/{flashcard}
     * Xóa flashcard.
     */
    public function destroy(Request $request, Flashcard $flashcard): JsonResponse
    {
        $flashcardSet = $flashcard->flashcardSet;

        if ($flashcardSet->user_id !== $request->user()->id) {
            return response()->json([
                'success' => false,
                'message' => 'Bạn không có quyền xóa flashcard này.',
            ], 403);
        }

        $flashcard->delete();

        return response()->json([
            'success' => true,
            'message' => 'Xóa flashcard thành công.',
        ]);
    }
}
