<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\AIQuizFromFileRequest;
use App\Models\Category;
use App\Models\Exam;
use App\Models\Question;
use App\Services\AIQuizFromFileService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Exception;

class AIQuizController extends Controller
{
    private AIQuizFromFileService $aiQuizFromFileService;

    public function __construct(AIQuizFromFileService $aiQuizFromFileService)
    {
        $this->aiQuizFromFileService = $aiQuizFromFileService;
    }

    /**
     * Generate quiz from uploaded file (PDF, DOCX, TXT)
     */
    public function generateFromFile(AIQuizFromFileRequest $request)
    {
        try {
            $numberOfQuestions = $request->input('number_of_questions', 5);
            $file = $request->file('file');

            $exam = $this->aiQuizFromFileService->generateQuizFromFile($file, $numberOfQuestions, []);

            return response()->json([
                'message' => 'Quiz generated successfully from file',
                'exam' => $exam,
            ], 201);

        } catch (Exception $e) {
            return response()->json([
                'message' => 'Failed to generate quiz from file',
                'error' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Generate quiz from text prompt (existing functionality)
     */
    public function generate(Request $request)
    {
        $data = $request->validate([
            'prompt' => 'required|string',
            'number_of_questions' => 'nullable|integer|min:1|max:50',
        ]);

        $questionCount = $data['number_of_questions'] ?? 5;

        $geminiUrl = config('services.gemini.url');
        $geminiKey = config('services.gemini.key');

        if (empty($geminiUrl) || empty($geminiKey)) {
            return response()->json(['message' => 'Gemini API config is missing'], 500);
        }

        $instructions = $this->buildPrompt($data['prompt'], $questionCount);

        $response = Http::post($geminiUrl . '?key=' . $geminiKey, [
            'contents' => [
                [
                    'parts' => [
                        ['text' => $instructions],
                    ],
                ],
            ],
            'generationConfig' => [
                'temperature' => 0.4,
            ],
        ]);

        if (!$response->successful()) {
            return response()->json([
                'message' => 'Gemini API failed',
                'details' => $response->json(),
            ], 502);
        }

        $raw = data_get($response->json(), 'candidates.0.content.parts.0.text');
        $parsed = $this->parseGeminiResponse($raw);

        if (!$parsed || empty($parsed['title']) || empty($parsed['questions'])) {
            return response()->json(['message' => 'AI response is invalid'], 422);
        }

        $categoryId = Category::query()->value('id');

        if (!$categoryId) {
            return response()->json(['message' => 'No category available to assign quiz'], 422);
        }

        $exam = DB::transaction(function () use ($parsed, $categoryId) {
            $exam = Exam::create([
                'title' => $parsed['title'],
                'description' => $parsed['description'] ?? 'AI generated quiz',
                'category_id' => $categoryId,
                'difficulty' => 'Beginner',
                'duration' => max(10, count($parsed['questions']) * 2),
                'passing_score' => 70,
                'max_attempts' => 3,
                'learning_objectives' => $parsed['learning_objectives'] ?? null,
                'prerequisites' => $parsed['prerequisites'] ?? null,
                'tags' => $parsed['tags'] ?? null,
                'status' => 'draft', // waiting for admin approval
                'is_ai_generated' => true,
            ]);

            foreach ($parsed['questions'] as $index => $question) {
                $options = $this->formatOptions($question['options'] ?? []);
                $answerIndex = $question['answer'] ?? 0;
                $answerKey = array_keys($options)[$answerIndex] ?? array_keys($options)[0] ?? 'A';

                $qModel = Question::create([
                    'content' => $question['question'] ?? 'Question ' . ($index + 1),
                    'options' => $options,
                    'answer' => $answerKey,
                    'explanation' => $question['explanation'] ?? null,
                    'type' => count($options) === 2 ? 'true_false' : 'multiple_choice',
                    'points' => 1,
                ]);

                $exam->questions()->attach($qModel->id, ['order' => $index + 1]);
            }

            return $exam->load('questions');
        });

        return response()->json($exam, 201);
    }

    private function buildPrompt(string $userPrompt, int $questionCount): string
    {
        return <<<EOT
Bạn là hệ thống sinh câu hỏi trắc nghiệm thông minh. Hãy tạo đúng JSON, không markdown, không thêm giải thích.

Yêu cầu:
- Ngôn ngữ giữ nguyên theo prompt người dùng.
- Số lượng câu hỏi: {$questionCount}.
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

    private function parseGeminiResponse(?string $raw): ?array
    {
        if (!$raw) {
            return null;
        }

        // Loại bỏ ```json ``` hoặc dấu nháy thừa
        $clean = trim($raw);
        $clean = Str::of($clean)
            ->replace('```json', '')
            ->replace('```', '')
            ->trim()
            ->value();

        $decoded = json_decode($clean, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            return null;
        }

        // Validate required fields
        if (!isset($decoded['title']) || !isset($decoded['questions']) || !is_array($decoded['questions'])) {
            return null;
        }

        // Add default metadata if not provided by AI
        if (!isset($decoded['description'])) {
            $decoded['description'] = 'AI generated quiz';
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

        return $decoded;
    }

    private function formatOptions(array $options): array
    {
        $options = array_values(array_filter($options, fn($opt) => filled($opt)));
        $letters = range('A', 'Z');
        $formatted = [];

        foreach ($options as $idx => $value) {
            $formatted[$letters[$idx] ?? 'Z'] = $value;
        }

        return $formatted ?: ['A' => 'Đáp án 1', 'B' => 'Đáp án 2'];
    }
}

