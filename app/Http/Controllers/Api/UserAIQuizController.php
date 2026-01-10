<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\UserAIQuizFromFileRequest;
use App\Http\Requests\UserAIQuizFromPromptRequest;
use App\Models\TokenPricing;
use App\Services\AIQuizFromFileService;
use App\Services\AIQuizFromPromptService;
use Exception;

class UserAIQuizController extends Controller
{
    private AIQuizFromFileService $aiQuizFromFileService;
    private AIQuizFromPromptService $aiQuizFromPromptService;

    public function __construct(
        AIQuizFromFileService $aiQuizFromFileService,
        AIQuizFromPromptService $aiQuizFromPromptService
    ) {
        $this->aiQuizFromFileService = $aiQuizFromFileService;
        $this->aiQuizFromPromptService = $aiQuizFromPromptService;
    }

    /**
     * Generate quiz from uploaded file with full quiz information
     * POST /api/user/exams/ai-generate-from-file
     */
    public function generateFromFile(UserAIQuizFromFileRequest $request)
    {
        try {
            $numberOfQuestions = $request->input('number_of_questions', 5);
            $file = $request->file('file');
            
            // Collect quiz information from request
            $quizInfo = [
                'title' => $request->input('title'),
                'description' => $request->input('description'),
                'category_id' => $request->input('category_id'),
                'difficulty' => $request->input('difficulty'),
                'duration' => $request->input('duration'),
                'color' => $request->input('color'),
                'passing_score' => $request->input('passing_score'),
                'max_attempts' => $request->input('max_attempts'),
                // Only include these if user explicitly provided them
                // Otherwise, let AI generate them
            ];
            
            // Add optional fields only if provided by user
            if ($request->has('learning_objectives') && !empty($request->input('learning_objectives'))) {
                $quizInfo['learning_objectives'] = $request->input('learning_objectives');
            }
            if ($request->has('prerequisites') && !empty($request->input('prerequisites'))) {
                $quizInfo['prerequisites'] = $request->input('prerequisites');
            }
            if ($request->has('tags') && !empty($request->input('tags'))) {
                $quizInfo['tags'] = $request->input('tags');
            }

            // Generate quiz with AI
            $exam = $this->aiQuizFromFileService->generateQuizFromFile(
                $file, 
                $numberOfQuestions, 
                $quizInfo
            );

            // Set token pricing if provided
            if ($request->has('price_token')) {
                TokenPricing::updateOrCreate(
                    [
                        'target_type' => 'quiz',
                        'target_id' => $exam->id
                    ],
                    [
                        'price_token' => $request->input('price_token', 0)
                    ]
                );
                
                // Add price to response
                $exam->price_token = $request->input('price_token', 0);
            }

            return response()->json([
                'message' => 'Quiz được tạo thành công từ file với AI',
                'data' => $exam->load('questions', 'category'),
            ], 201);

        } catch (Exception $e) {
            return response()->json([
                'message' => 'Không thể tạo quiz từ file',
                'error' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Generate quiz from text prompt with full quiz information
     * POST /api/user/exams/ai-generate-from-prompt
     */
    public function generateFromPrompt(UserAIQuizFromPromptRequest $request)
    {
        try {
            $numberOfQuestions = $request->input('number_of_questions', 5);
            $prompt = $request->input('prompt');
            
            // Collect quiz information from request
            $quizInfo = [
                'title' => $request->input('title'),
                'description' => $request->input('description'),
                'category_id' => $request->input('category_id'),
                'difficulty' => $request->input('difficulty'),
                'duration' => $request->input('duration'),
                'color' => $request->input('color'),
                'passing_score' => $request->input('passing_score'),
                'max_attempts' => $request->input('max_attempts'),
            ];
            
            // Add optional fields only if provided by user
            // Otherwise, let AI generate them
            if ($request->has('learning_objectives') && !empty($request->input('learning_objectives'))) {
                $quizInfo['learning_objectives'] = $request->input('learning_objectives');
            }
            if ($request->has('prerequisites') && !empty($request->input('prerequisites'))) {
                $quizInfo['prerequisites'] = $request->input('prerequisites');
            }
            if ($request->has('tags') && !empty($request->input('tags'))) {
                $quizInfo['tags'] = $request->input('tags');
            }

            // Generate quiz with AI
            $exam = $this->aiQuizFromPromptService->generateQuizFromPrompt(
                $prompt, 
                $numberOfQuestions, 
                $quizInfo
            );

            // Set token pricing if provided
            if ($request->has('price_token')) {
                TokenPricing::updateOrCreate(
                    [
                        'target_type' => 'quiz',
                        'target_id' => $exam->id
                    ],
                    [
                        'price_token' => $request->input('price_token', 0)
                    ]
                );
                
                // Add price to response
                $exam->price_token = $request->input('price_token', 0);
            }

            return response()->json([
                'message' => 'Quiz được tạo thành công từ prompt với AI',
                'data' => $exam->load('questions', 'category'),
            ], 201);

        } catch (Exception $e) {
            return response()->json([
                'message' => 'Không thể tạo quiz từ prompt',
                'error' => $e->getMessage(),
            ], 422);
        }
    }
}