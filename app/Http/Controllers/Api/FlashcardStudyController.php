<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\FlashcardProgressResource;
use App\Http\Resources\FlashcardSetResource;
use App\Models\Flashcard;
use App\Models\FlashcardSet;
use App\Models\FlashcardProgress;
use App\Services\GamificationService;
use App\Services\AchievementService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class FlashcardStudyController extends Controller
{
    public function __construct(
        private GamificationService $gamificationService,
        private AchievementService $achievementService
    ) {
    }

    /**
     * GET /api/flashcard-sets/{flashcardSet}/study
     * Lấy dữ liệu học.
     */
    public function study(Request $request, FlashcardSet $flashcardSet): JsonResponse
    {
        if (
            $flashcardSet->user_id !== $request->user()->id &&
            (
                $flashcardSet->visibility !== 'public' ||
                $flashcardSet->status !== 'published'
            )
        ) {
            return response()->json([
                'success' => false,
                'message' => 'Bạn không có quyền truy cập bộ thẻ này.',
            ], 403);
        }

        $flashcardSet->load([
            'category:id,name',
            'flashcards.progress' => function ($query) use ($request) {
                $query->where('user_id', $request->user()->id);
            },
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
            'message' => 'Lấy dữ liệu học thành công.',
            'data'    => new FlashcardSetResource($flashcardSet),
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

        $this->gamificationService->reward($request->user(), 5, 'flashcard_review', $flashcard->id, 'Review flashcard');

        $unlockedAchievements = $this->achievementService->checkAndUnlock($request->user(), 'flashcard_reviewed');

        return response()->json([
            'success' => true,
            'message' => 'Cập nhật tiến độ học thành công.',
            'data'    => new FlashcardProgressResource($progress),
            'unlocked_achievements' => $unlockedAchievements,
        ]);
    }
}
