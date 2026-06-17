<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\FlashcardSetResource;
use App\Models\FlashcardSet;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class FlashcardSetController extends Controller
{
    public function __construct(
        private NotificationService $notificationService
    ) {
    }

    /**
     * GET /api/flashcard-sets
     * Danh sách bộ thẻ của user hiện tại.
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        $query = FlashcardSet::with('category:id,name')
            ->withCount('flashcards')
            ->withCount([
                'flashcards as mastered_count' => function ($query) use ($user) {
                    $query->whereHas('progress', function ($query) use ($user) {
                        $query->where('user_id', $user->id)
                            ->where('status', 'mastered');
                    });
                },
            ])
            ->where('user_id', $user->id)
            ->latest();

        if (!$request->filled('status')) {
            $query->where('status', '!=', 'archived');
        }

        // Lọc theo trạng thái
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('visibility')) {
            $query->where('visibility', $request->visibility);
        }

        // Lọc theo category
        if ($request->filled('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        // Lọc theo nguồn tạo
        if ($request->filled('source_type')) {
            $query->where('source_type', $request->source_type);
        }

        // Tìm kiếm theo tiêu đề hoặc mô tả
        if ($request->filled('search')) {
            $search = $request->search;

            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        $flashcardSets = $query->get();

        return response()->json([
            'success' => true,
            'data' => FlashcardSetResource::collection($flashcardSets),
        ]);
    }

    /**
     * GET /api/flashcard-sets/public
     * Danh sách bộ thẻ public đã published.
     */
    public function publicIndex(Request $request): JsonResponse
    {
        $user = $request->user();

        $query = FlashcardSet::with(['category:id,name', 'user:id,name'])
            ->withCount('flashcards')
            ->withCount([
                'flashcards as mastered_count' => function ($query) use ($user) {
                    $query->whereHas('progress', function ($query) use ($user) {
                        $query->where('user_id', $user->id)
                            ->where('status', 'mastered');
                    });
                },
            ])
            ->where('visibility', 'public')
            ->where('status', 'published')
            ->latest();

        if ($request->filled('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        if ($request->filled('source_type')) {
            $query->where('source_type', $request->source_type);
        }

        return response()->json([
            'success' => true,
            'data' => FlashcardSetResource::collection($query->get()),
        ]);
    }

    /**
     * GET /api/flashcard-sets/summary
     * Tổng hợp tiến độ flashcard của user hiện tại.
     */
    public function summary(Request $request): JsonResponse
    {
        $userId = $request->user()->id;

        $setQuery = FlashcardSet::where('user_id', $userId)
            ->where('status', '!=', 'archived');

        $totalSets = (clone $setQuery)->count();

        $totalCards = DB::table('flashcards')
            ->join('flashcard_sets', 'flashcards.flashcard_set_id', '=', 'flashcard_sets.id')
            ->where('flashcard_sets.user_id', $userId)
            ->where('flashcard_sets.status', '!=', 'archived')
            ->count('flashcards.id');

        $progressQuery = DB::table('flashcard_progress')
            ->join('flashcards', 'flashcard_progress.flashcard_id', '=', 'flashcards.id')
            ->join('flashcard_sets', 'flashcards.flashcard_set_id', '=', 'flashcard_sets.id')
            ->where('flashcard_progress.user_id', $userId)
            ->where('flashcard_sets.user_id', $userId)
            ->where('flashcard_sets.status', '!=', 'archived');

        $reviewedCards = (clone $progressQuery)
            ->distinct('flashcard_progress.flashcard_id')
            ->count('flashcard_progress.flashcard_id');

        $masteredCount = (clone $progressQuery)
            ->where('flashcard_progress.status', 'mastered')
            ->count();

        $needsReviewCount = (clone $progressQuery)
            ->where('flashcard_progress.status', 'learning')
            ->count();

        $dueReviewCount = (clone $progressQuery)
            ->where('flashcard_progress.status', 'learning')
            ->where(function ($query) {
                $query->whereNull('flashcard_progress.next_review_at')
                    ->orWhere('flashcard_progress.next_review_at', '<=', now());
            })
            ->count();

        $newCount = max(0, $totalCards - $reviewedCards);

        $setsBySource = (clone $setQuery)
            ->select('source_type', DB::raw('count(*) as total'))
            ->groupBy('source_type')
            ->pluck('total', 'source_type');

        return response()->json([
            'success' => true,
            'data' => [
                'totalSets' => $totalSets,
                'totalCards' => $totalCards,
                'masteredCount' => $masteredCount,
                'needsReviewCount' => $needsReviewCount,
                'dueReviewCount' => $dueReviewCount,
                'newCount' => $newCount,
                'setsBySource' => [
                    'manual' => (int) ($setsBySource['manual'] ?? 0),
                    'quizWrongAnswers' => (int) ($setsBySource['quiz_wrong_answers'] ?? 0),
                    'aiGenerated' => (int) ($setsBySource['ai_generated'] ?? 0),
                ],
            ],
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
            'visibility'  => ['nullable', 'in:private,public'],
            'source_type' => ['nullable', 'in:manual,quiz_wrong_answers,ai_generated'],
            'status'      => ['nullable', 'in:draft,published,archived'],
            'exam_id'     => ['nullable', 'exists:exams,id'],
        ]);

        $sourceType = $validated['source_type'] ?? 'manual';

        $flashcardSet = FlashcardSet::create([
            'user_id'     => $request->user()->id,
            'title'       => $validated['title'],
            'category_id' => $validated['category_id'] ?? null,
            'description' => $validated['description'] ?? null,
            'visibility'  => $validated['visibility'] ?? 'private',
            'source_type' => $sourceType,
            'exam_id'     => $validated['exam_id'] ?? null,
            'status'      => $validated['status'] ?? 'published',
        ]);

        try {
            $this->notificationService->flashcardSetCreated($request->user()->id, [
                'flashcard_set_id' => $flashcardSet->id,
                'flashcard_set_title' => $flashcardSet->title,
                'cards_count' => 0,
                'source_type' => $flashcardSet->source_type,
                'exam_id' => $flashcardSet->exam_id,
            ]);
        } catch (\Throwable $e) {
            Log::warning('Failed to create flashcard set notification', [
                'user_id' => $request->user()->id,
                'flashcard_set_id' => $flashcardSet->id,
                'error' => $e->getMessage(),
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Tạo bộ thẻ thành công.',
            'data'    => new FlashcardSetResource(
                $flashcardSet->load('category:id,name')->loadCount('flashcards')
            ),
        ], 201);
    }

    /**
     * GET /api/flashcard-sets/{flashcardSet}
     * Xem chi tiết bộ thẻ.
     */
    public function show(Request $request, FlashcardSet $flashcardSet): JsonResponse
    {
        if (!$this->canView($request, $flashcardSet)) {
            return response()->json([
                'success' => false,
                'message' => 'Bạn không có quyền truy cập bộ thẻ này.',
            ], 403);
        }

        $flashcardSet->load([
            'flashcards.progress' => function ($query) use ($request) {
                $query->where('user_id', $request->user()->id);
            },
            'user:id,name',
            'exam:id,title',
            'category:id,name',
        ])->loadCount('flashcards')
            ->loadCount([
                'flashcards as mastered_count' => function ($query) use ($request) {
                    $query->whereHas('progress', function ($query) use ($request) {
                        $query->where('user_id', $request->user()->id)
                            ->where('status', 'mastered');
                    });
                },
            ]);

        return response()->json([
            'success' => true,
            'message' => 'Lấy chi tiết bộ thẻ thành công.',
            'data'    => new FlashcardSetResource($flashcardSet),
        ]);
    }

    /**
     * PUT/PATCH /api/flashcard-sets/{flashcardSet}
     * Cập nhật bộ thẻ.
     */
    public function update(Request $request, FlashcardSet $flashcardSet): JsonResponse
    {
        if ($flashcardSet->user_id !== $request->user()->id) {
            return response()->json([
                'success' => false,
                'message' => 'Bạn không có quyền chỉnh sửa bộ thẻ này.',
            ], 403);
        }

        $validated = $request->validate([
            'title'       => ['sometimes', 'required', 'string', 'max:255'],
            'category_id' => ['nullable', 'exists:categories,id'],
            'description' => ['nullable', 'string'],
            'visibility'  => ['nullable', 'in:private,public'],
            'source_type' => ['nullable', 'in:manual,quiz_wrong_answers,ai_generated'],
            'status'      => ['nullable', 'in:draft,published,archived'],
            'exam_id'     => ['nullable', 'exists:exams,id'],
        ]);

        $flashcardSet->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Cập nhật bộ thẻ thành công.',
            'data'    => new FlashcardSetResource(
                $flashcardSet->fresh()->load('category:id,name')->loadCount('flashcards')
            ),
        ]);
    }

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

        $flashcardSet->update([
            'status' => 'archived',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Đã lưu trữ bộ thẻ thành công.',
        ]);
    }

    /**
     * POST /api/flashcard-sets/{flashcardSet}/publish
     * Public bộ thẻ của owner.
     */
    public function publish(Request $request, FlashcardSet $flashcardSet): JsonResponse
    {
        if ($flashcardSet->user_id !== $request->user()->id) {
            return response()->json([
                'success' => false,
                'message' => 'Bạn không có quyền public bộ thẻ này.',
            ], 403);
        }

        $flashcardSet->update([
            'visibility' => 'public',
            'status' => 'published',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Đã public bộ thẻ thành công.',
            'data'    => new FlashcardSetResource(
                $flashcardSet->fresh()->load(['category:id,name', 'user:id,name'])->loadCount('flashcards')
            ),
        ]);
    }

    /**
     * GET /api/flashcard-sets/{flashcardSet}/results
     * Lấy kết quả học tập của bộ thẻ (dành cho owner).
     */
    public function submit(Request $request, FlashcardSet $flashcardSet): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => 'API submit đã deprecated. Vui lòng dùng POST /api/flashcard-sets/{flashcardSet}/publish.',
        ], 410);
    }

    /**
     * Hàm kiểm tra quyền xem bộ thẻ
     * Owner có thể xem tất cả, người khác chỉ xem được nếu bộ thẻ public và đã published.
     */
    private function canView(Request $request, FlashcardSet $flashcardSet): bool
    {
        return $flashcardSet->user_id === $request->user()->id
            || (
                $flashcardSet->visibility === 'public'
                && $flashcardSet->status === 'published'
            );
    }
}
