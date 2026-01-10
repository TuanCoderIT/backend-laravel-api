<?php

namespace App\Services;

use App\Models\Category;
use App\Models\Exam;
use App\Models\Question;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class AIQuizFromPromptService
{
    public function generateQuizFromPrompt(string $prompt, int $numberOfQuestions = 5, array $quizInfo = []): Exam
    {
        // Generate quiz using AI
        $aiResponse = $this->callAIService($prompt, $numberOfQuestions);
        
        // Parse AI response
        $parsedQuiz = $this->parseAIResponse($aiResponse);
        
        if (!$parsedQuiz || empty($parsedQuiz['title']) || empty($parsedQuiz['questions'])) {
            throw new Exception('AI failed to generate a valid quiz structure');
        }

        // Create quiz in database with user-provided info and AI-generated metadata
        return $this->createQuizInDatabase($parsedQuiz, $quizInfo);
    }

    private function callAIService(string $prompt, int $numberOfQuestions): string
    {
        $geminiUrl = config('services.gemini.url');
        $geminiKey = config('services.gemini.key');

        if (empty($geminiUrl) || empty($geminiKey)) {
            throw new Exception('Gemini API configuration is missing');
        }

        $aiPrompt = $this->buildAIPrompt($prompt, $numberOfQuestions);

        $response = Http::timeout(60)->post($geminiUrl . '?key=' . $geminiKey, [
            'contents' => [
                [
                    'parts' => [
                        ['text' => $aiPrompt],
                    ],
                ],
            ],
            'generationConfig' => [
                'temperature' => 0.4,
                'maxOutputTokens' => 2048,
            ],
        ]);

        if (!$response->successful()) {
            throw new Exception('AI service failed: ' . $response->body());
        }

        $aiText = data_get($response->json(), 'candidates.0.content.parts.0.text');
        
        if (empty($aiText)) {
            throw new Exception('AI service returned empty response');
        }

        return $aiText;
    }

    private function buildAIPrompt(string $userPrompt, int $numberOfQuestions): string
    {
        return <<<EOT
Bạn là hệ thống sinh câu hỏi trắc nghiệm thông minh. Hãy tạo đúng JSON, không markdown, không thêm giải thích.

Yêu cầu:
- Ngôn ngữ giữ nguyên theo prompt người dùng.
- Số lượng câu hỏi: {$numberOfQuestions}.
- Chỉ tạo dạng multiple_choice hoặc true_false.
- Mỗi câu hỏi phải có đầy đủ nội dung, options (mảng string), answer (chỉ số option đúng, bắt đầu từ 0).
- Tạo thêm mục tiêu học tập (3-5 mục), kiến thức tiên quyết (2-4 mục), và tags (3-6 từ khóa).

Trả về đúng JSON sau:
{
  "title": "Tiêu đề quiz phù hợp",
  "description": "Mô tả ngắn gọn về quiz",
  "learning_objectives": [
    "Học viên sẽ hiểu được khái niệm A",
    "Học viên có thể áp dụng kỹ thuật B",
    "Học viên phân tích được tình huống C"
  ],
  "prerequisites": [
    "Kiến thức cơ bản về chủ đề X",
    "Hiểu biết về khái niệm Y"
  ],
  "tags": [
    "từ-khóa-1",
    "chủ-đề-2",
    "lĩnh-vực-3"
  ],
  "questions": [
    {
      "question": "Nội dung câu hỏi",
      "options": ["Đáp án A", "Đáp án B", "Đáp án C", "Đáp án D"],
      "answer": 0,
      "explanation": "Giải thích ngắn gọn"
    }
  ]
}

Prompt người dùng: {$userPrompt}
EOT;
    }

    private function parseAIResponse(string $aiResponse): ?array
    {
        // Clean the response
        $cleanResponse = trim($aiResponse);
        
        // Remove markdown code blocks if present
        $cleanResponse = preg_replace('/```json\s*/', '', $cleanResponse);
        $cleanResponse = preg_replace('/```\s*$/', '', $cleanResponse);
        $cleanResponse = trim($cleanResponse);

        // Attempt to decode JSON
        $decoded = json_decode($cleanResponse, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception('AI returned invalid JSON: ' . json_last_error_msg());
        }

        // Validate structure
        if (!isset($decoded['title']) || !isset($decoded['questions']) || !is_array($decoded['questions'])) {
            throw new Exception('AI response missing required fields (title, questions)');
        }

        // Add default metadata if not provided by AI
        if (!isset($decoded['description'])) {
            $decoded['description'] = 'AI generated quiz from prompt';
        }
        if (!isset($decoded['learning_objectives']) || !is_array($decoded['learning_objectives'])) {
            $decoded['learning_objectives'] = [];
        }
        if (!isset($decoded['prerequisites']) || !is_array($decoded['prerequisites'])) {
            $decoded['prerequisites'] = [];
        }
        if (!isset($decoded['tags']) || !is_array($decoded['tags'])) {
            $decoded['tags'] = [];
        }

        // Validate each question
        foreach ($decoded['questions'] as $index => $question) {
            if (!isset($question['question']) || !isset($question['options']) || !isset($question['answer'])) {
                throw new Exception("Question {$index} is missing required fields");
            }

            if (!is_array($question['options']) || count($question['options']) < 2) {
                throw new Exception("Question {$index} must have at least 2 options");
            }

            if (!is_int($question['answer']) || $question['answer'] < 0 || $question['answer'] >= count($question['options'])) {
                throw new Exception("Question {$index} has invalid answer index");
            }
        }

        return $decoded;
    }

    private function createQuizInDatabase(array $quizData, array $quizInfo = []): Exam
    {
        return DB::transaction(function () use ($quizData, $quizInfo) {
            // Use user-provided info or fallback to defaults
            $categoryId = $quizInfo['category_id'] ?? Category::first()?->id;
            
            if (!$categoryId) {
                throw new Exception('No category available. Please create at least one category first.');
            }

            // Create the exam with user-provided information and AI-generated metadata
            $exam = Exam::create([
                'title' => $quizInfo['title'] ?? $quizData['title'],
                'description' => $quizInfo['description'] ?? $quizData['description'] ?? 'AI generated quiz from prompt',
                'category_id' => $categoryId,
                'difficulty' => $quizInfo['difficulty'] ?? 'Beginner',
                'duration' => $quizInfo['duration'] ?? max(10, count($quizData['questions']) * 2),
                'color' => $quizInfo['color'] ?? null,
                'passing_score' => $quizInfo['passing_score'] ?? 70,
                'max_attempts' => $quizInfo['max_attempts'] ?? 3,
                // Use AI-generated metadata if user didn't provide, otherwise use user input
                'learning_objectives' => $quizInfo['learning_objectives'] ?? $quizData['learning_objectives'] ?? null,
                'prerequisites' => $quizInfo['prerequisites'] ?? $quizData['prerequisites'] ?? null,
                'tags' => $quizInfo['tags'] ?? $quizData['tags'] ?? null,
                'status' => 'draft', // Always draft for user-created quizzes
                'is_ai_generated' => true,
            ]);

            // Create questions and attach to exam
            foreach ($quizData['questions'] as $index => $questionData) {
                $options = $this->formatQuestionOptions($questionData['options']);
                $answerKey = array_keys($options)[$questionData['answer']] ?? 'A';

                $question = Question::create([
                    'content' => $questionData['question'],
                    'options' => $options,
                    'answer' => $answerKey,
                    'explanation' => $questionData['explanation'] ?? null,
                    'type' => count($options) === 2 ? 'true_false' : 'multiple_choice',
                    'points' => 1,
                ]);

                $exam->questions()->attach($question->id, ['order' => $index + 1]);
            }

            return $exam->load('questions');
        });
    }

    private function formatQuestionOptions(array $options): array
    {
        $letters = ['A', 'B', 'C', 'D', 'E', 'F'];
        $formatted = [];

        foreach ($options as $index => $option) {
            $letter = $letters[$index] ?? chr(65 + $index); // A, B, C, D, E, F...
            $formatted[$letter] = trim($option);
        }

        return $formatted;
    }
}