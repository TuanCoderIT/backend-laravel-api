<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\FlashcardSet;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class FlashcardSetController extends Controller
{
    /**
     * GET /api/flashcard-sets
     * Danh sách bộ thẻ của user hiện tại.
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        $query = FlashcardSet::withCount('flashcards')
            ->where('user_id', $user->id)
            ->latest();

        // Lọc theo trạng thái
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Lọc theo category
        if ($request->filled('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        // Lọc theo nguồn tạo
        if ($request->filled('source_type')) {
            $query->where('source_type', $request->source_type);
        }

        // // Tìm kiếm theo tiêu đề hoặc mô tả
        // if ($request->filled('search')) {
        //     $search = $request->search;

        //     $query->where(function ($q) use ($search) {
        //         $q->where('title', 'like', "%{$search}%")
        //             ->orWhere('description', 'like', "%{$search}%");
        //     });
        // }

        $flashcardSets = $query->get();

        return response()->json([
            'success' => true,
            'data' => $flashcardSets,
        ]);
    }

    /**
     * POST /api/flashcard-sets
     * Tạo bộ thẻ mới.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'title'       => ['required', 'string', 'max:255'],
            'category_id' => ['nullable', 'exists:categories,id'],
            'description' => ['nullable', 'string'],
            'source_type' => ['nullable', 'in:manual,quiz_wrong_answers,ai_generated'],
            'status'      => ['nullable', 'in:draft,pending,published'],
            'exam_id'     => ['nullable', 'exists:exams,id'],
            'color'       => ['nullable', 'string', 'max:20'],
        ]);

        // Tự động xác định nếu là AI-generated
        $sourceType = $validated['source_type'] ?? 'manual';

        $flashcardSet = FlashcardSet::create([
            'user_id'         => $request->user()->id,
            'title'           => $validated['title'],
            'category_id'     => $validated['category_id'] ?? null,
            'description'     => $validated['description'] ?? null,
            'source_type'     => $sourceType,
            'is_ai_generated' => $sourceType === 'ai_generated',
            'exam_id'         => $validated['exam_id'] ?? null,
            'color'           => $validated['color'] ?? '#4F46E5',
            'status'          => $validated['status'] ?? 'draft',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Tạo bộ thẻ thành công.',
            'data'    => $flashcardSet->loadCount('flashcards'),
        ], 201);
    }

    /**
     * GET /api/flashcard-sets/{flashcardSet}
     * Xem chi tiết bộ thẻ.
     */
    public function show(Request $request, FlashcardSet $flashcardSet): JsonResponse
    {
        // Chỉ chủ sở hữu mới được xem (có thể mở rộng cho published sets)
        if ($flashcardSet->user_id !== $request->user()->id) {
            return response()->json([
                'success' => false,
                'message' => 'Bạn không có quyền truy cập bộ thẻ này.',
            ], 403);
        }

        $flashcardSet->load([
            'flashcards',
            'user:id,name',
            'reviewer:id,name',
            'exam:id,title',
            'category:id,name',
        ])->loadCount('flashcards');

        return response()->json([
            'success' => true,
            'message' => 'Lấy chi tiết bộ thẻ thành công.',
            'data'    => $flashcardSet,
        ]);
    }

    /**
     * PUT/PATCH /api/flashcard-sets/{flashcardSet}
     * Cập nhật bộ thẻ.
     */
    public function update(Request $request, FlashcardSet $flashcardSet): JsonResponse
    {
        // Nếu bạn là Admin, có thể bạn muốn bỏ qua check này để sửa mọi lúc
        if ($flashcardSet->user_id !== $request->user()->id && !$request->user()->is_admin) {
            return response()->json([
                'success' => false,
                'message' => 'Bạn không có quyền chỉnh sửa bộ thẻ này.',
            ], 403);
        }

        $validated = $request->validate([
            'title'       => ['sometimes', 'required', 'string', 'max:255'],
            'category_id' => ['nullable', 'exists:categories,id'],
            'description' => ['nullable', 'string'],
            'status'      => ['nullable', 'in:draft,pending,published,rejected,archived'],
            'color'       => ['nullable', 'string', 'max:20'],
        ]);

        $flashcardSet->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Cập nhật bộ thẻ thành công.',
            'data'    => $flashcardSet->fresh()->loadCount('flashcards'),
        ]);
    }

    // public function update(Request $request, FlashcardSet $flashcardSet): JsonResponse
    // {
    //     if ($flashcardSet->user_id !== $request->user()->id) {
    //         return response()->json([
    //             'success' => false,
    //             'message' => 'Bạn không có quyền chỉnh sửa bộ thẻ này.',
    //         ], 403);
    //     }

    //     // Không cho sửa khi đang chờ duyệt hoặc đã publish
    //     if (in_array($flashcardSet->status, ['pending', 'published'])) {
    //         return response()->json([
    //             'success' => false,
    //             'message' => 'Không thể chỉnh sửa bộ thẻ ở trạng thái hiện tại.',
    //         ], 422);
    //     }

    //     $validated = $request->validate([
    //         'title'       => ['sometimes', 'required', 'string', 'max:255'],
    //         'description' => ['nullable', 'string'],
    //         'color'       => ['nullable', 'string', 'max:20'],
    //     ]);

    //     $flashcardSet->update($validated);

    //     return response()->json([
    //         'success' => true,
    //         'message' => 'Cập nhật bộ thẻ thành công.',
    //         'data'    => $flashcardSet->fresh()->loadCount('flashcards'),
    //     ]);
    // }

    /**
     * DELETE /api/flashcard-sets/{flashcardSet}
     * Xóa bộ thẻ.
     */
    public function destroy(Request $request, FlashcardSet $flashcardSet): JsonResponse
    {
        if ($flashcardSet->user_id !== $request->user()->id) {
            return response()->json([
                'success' => false,
                'message' => 'Bạn không có quyền xóa bộ thẻ này.',
            ], 403);
        }

        $flashcardSet->delete();

        return response()->json([
            'success' => true,
            'message' => 'Xóa bộ thẻ thành công.',
        ]);
    }

    /**
     * POST /api/flashcard-sets/{flashcardSet}/submit
     * Gửi bộ thẻ để admin duyệt.
     */
    public function submit(Request $request, FlashcardSet $flashcardSet): JsonResponse
    {
        if ($flashcardSet->user_id !== $request->user()->id) {
            return response()->json([
                'success' => false,
                'message' => 'Bạn không có quyền gửi duyệt bộ thẻ này.',
            ], 403);
        }

        if ($flashcardSet->status !== 'draft') {
            return response()->json([
                'success' => false,
                'message' => 'Chỉ có thể gửi duyệt bộ thẻ ở trạng thái draft.',
            ], 422);
        }

        if ($flashcardSet->flashcards()->count() === 0) {
            return response()->json([
                'success' => false,
                'message' => 'Bộ thẻ phải có ít nhất một thẻ trước khi gửi duyệt.',
            ], 422);
        }

        $flashcardSet->update([
            'status'       => 'pending',
            'submitted_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Đã gửi bộ thẻ để chờ admin duyệt.',
            'data'    => $flashcardSet->fresh()->loadCount('flashcards'),
        ]);
    }
}
