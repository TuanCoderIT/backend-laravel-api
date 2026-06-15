<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\AIFlashcardFromFileRequest;
use App\Http\Requests\AIFlashcardFromPromptRequest;
use App\Http\Resources\FlashcardSetResource;
use App\Services\AIFlashcardService;
use Exception;
use Illuminate\Http\JsonResponse;

class UserAIFlashcardController extends Controller
{
    private AIFlashcardService $aiFlashcardService;

    public function __construct(AIFlashcardService $aiFlashcardService)
    {
        $this->aiFlashcardService = $aiFlashcardService;
    }

    /**
     * Generate flashcard set from a text prompt for user
     * POST /api/flashcard-sets/ai-generate-from-prompt
     */
    public function generateFromPrompt(AIFlashcardFromPromptRequest $request): JsonResponse
    {
        try {
            $numberOfCards = $request->input('number_of_cards', 5);
            $prompt = $request->input('prompt');
            $userId = $request->user()->id;

            $setInfo = [
                'title' => $request->input('title'),
                'description' => $request->input('description'),
                'category_id' => $request->input('category_id'),
                'visibility' => $request->input('visibility', 'private'),
                'status' => 'published', // standard published state for user sets
            ];

            $flashcardSet = $this->aiFlashcardService->generateFromPrompt($prompt, $numberOfCards, $setInfo, $userId);

            return response()->json([
                'success' => true,
                'message' => 'Bộ thẻ flashcard được tạo thành công từ prompt với AI',
                'data' => new FlashcardSetResource(
                    $flashcardSet->load('category:id,name', 'flashcards')->loadCount('flashcards')
                ),
            ], 201);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Không thể tạo bộ thẻ từ prompt',
                'error' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Generate flashcard set from uploaded file for user
     * POST /api/flashcard-sets/ai-generate-from-file
     */
    public function generateFromFile(AIFlashcardFromFileRequest $request): JsonResponse
    {
        try {
            $numberOfCards = $request->input('number_of_cards', 5);
            $file = $request->file('file');
            $userId = $request->user()->id;

            $setInfo = [
                'title' => $request->input('title'),
                'description' => $request->input('description'),
                'category_id' => $request->input('category_id'),
                'visibility' => $request->input('visibility', 'private'),
                'status' => 'published',
            ];

            $flashcardSet = $this->aiFlashcardService->generateFromFile($file, $numberOfCards, $setInfo, $userId);

            return response()->json([
                'success' => true,
                'message' => 'Bộ thẻ flashcard được tạo thành công từ file với AI',
                'data' => new FlashcardSetResource(
                    $flashcardSet->load('category:id,name', 'flashcards')->loadCount('flashcards')
                ),
            ], 201);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Không thể tạo bộ thẻ từ file',
                'error' => $e->getMessage(),
            ], 422);
        }
    }
}
