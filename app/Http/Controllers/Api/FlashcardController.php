<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
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
        
        // if (in_array($flashcardSet->status, ['pending', 'published'])) {
        //     return response()->json([
        //         'success' => false,
        //         'message' => 'Không thể chỉnh sửa bộ thẻ ở trạng thái hiện tại.',
        //     ], 422);
        // }

        $validated = $request->validate([
            'front_text'  => ['required', 'string'],
            'back_text'   => ['required', 'string'],
            'explanation' => ['nullable', 'string'],
        ]);

        $flashcard = $flashcardSet->flashcards()->create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Thêm flashcard thành công.',
            'data'    => $flashcard,
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

        // if (in_array($flashcardSet->status, ['pending', 'published'])) {
        //     return response()->json([
        //         'success' => false,
        //         'message' => 'Không thể chỉnh sửa bộ thẻ ở trạng thái hiện tại.',
        //     ], 422);
        // }

        $validated = $request->validate([
            'front_text'  => ['sometimes', 'required', 'string'],
            'back_text'   => ['sometimes', 'required', 'string'],
            'explanation' => ['nullable', 'string'],
        ]);

        $flashcard->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Cập nhật flashcard thành công.',
            'data'    => $flashcard->fresh(),
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

        if (in_array($flashcardSet->status, ['pending', 'published'])) {
            return response()->json([
                'success' => false,
                'message' => 'Không thể chỉnh sửa bộ thẻ ở trạng thái hiện tại.',
            ], 422);
        }

        $flashcard->delete();

        return response()->json([
            'success' => true,
            'message' => 'Xóa flashcard thành công.',
        ]);
    }
}