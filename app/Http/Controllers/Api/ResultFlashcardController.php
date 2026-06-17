<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\FlashcardResource;
use App\Http\Resources\FlashcardSetResource;
use App\Models\FlashcardSet;
use App\Models\Result;
use App\Services\GamificationService;
use App\Services\AchievementService;
use App\Services\NotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ResultFlashcardController extends Controller
{
    public function __construct(
        private GamificationService $gamificationService,
        private AchievementService $achievementService,
        private NotificationService $notificationService
    ) {}

    /**
     * POST /api/results/{result}/generate-wrong-answer-flashcards
     * Tao bo flashcard tu cac cau tra loi sai trong ket qua hien tai.
     */
    public function store(Request $request, Result $result): JsonResponse
    {
        if ($result->user_id !== $request->user()->id) {
            return response()->json([
                'success' => false,
                'message' => 'Bạn không có quyền truy cập kết quả này.',
            ], 403);
        }

        $result->load([
            'exam:id,title',
            'answers' => function ($query) {
                $query->where('is_correct', false)
                    ->with('question:id,content,options,answer,explanation');
            },
        ]);

        $wrongAnswers = $result->answers;

        if ($wrongAnswers->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'Không có câu trả lời sai để tạo flashcard.',
            ], 422);
        }

        $flashcardSet = DB::transaction(function () use ($request, $result, $wrongAnswers) {
            $examTitle = $result->exam?->title ?? 'Quiz';

            $flashcardSet = FlashcardSet::create([
                'user_id' => $request->user()->id,
                'title' => "Ôn tập câu sai - {$examTitle}",
                'description' => 'Bộ thẻ được tạo từ các câu trả lời sai trong bài quiz.',
                'visibility' => 'private',
                'source_type' => 'quiz_wrong_answers',
                'exam_id' => $result->exam_id,
                'status' => 'published',
            ]);

            $flashcardSet->flashcards()->createMany(
                $wrongAnswers->map(function ($answer) {
                    $question = $answer->question;

                    $correctKey = trim((string) $answer->correct_answer);
                    $backText = $correctKey;

                    $options = $question?->options ?? [];

                    if (is_string($options)) {
                        $options = json_decode($options, true) ?? [];
                    }

                    if (is_array($options) && array_key_exists($correctKey, $options)) {
                        // $backText = "{$correctKey}. {$options[$correctKey]}";
                        $backText = $options[$correctKey];
                    }

                    return [
                        'front_text' => $question?->content ?? 'Câu hỏi không tồn tại',
                        'back_text' => $backText,
                        'explanation' => $question?->explanation,
                    ];
                })->all()
            );

            return $flashcardSet
                ->load(['category:id,name', 'flashcards'])
                ->loadCount('flashcards');
        });

        $this->gamificationService->reward(
            $request->user(),
            15,
            'wrong_answer_flashcards',
            $flashcardSet->id,
            'Tạo flashcards từ câu sai'
        );

        $unlockedAchievements = $this->achievementService->checkAndUnlock(
            $request->user(),
            'wrong_answer_flashcards_created'
        );

        try {
            $this->notificationService->flashcardSetCreated($request->user()->id, [
                'flashcard_set_id' => $flashcardSet->id,
                'flashcard_set_title' => $flashcardSet->title,
                'cards_count' => $flashcardSet->flashcards_count,
                'source_type' => $flashcardSet->source_type,
                'exam_id' => $flashcardSet->exam_id,
            ]);
        } catch (\Throwable $e) {
            Log::warning('Failed to create wrong-answer flashcard notification', [
                'user_id' => $request->user()->id,
                'flashcard_set_id' => $flashcardSet->id,
                'error' => $e->getMessage(),
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Tạo flashcard từ câu trả lời sai thành công.',
            'data' => [
                'flashcardSet' => new FlashcardSetResource($flashcardSet),
                'flashcardsCount' => $flashcardSet->flashcards_count,
                'flashcards' => FlashcardResource::collection($flashcardSet->flashcards),
            ],
            'unlocked_achievements' => $unlockedAchievements,
        ], 201);
    }
}
