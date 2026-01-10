<?php

namespace App\Services;

use App\Models\Category;
use App\Models\Exam;
use App\Models\Question;
use Exception;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class AIQuizFromFileService
{
    private FileTextExtractor $textExtractor;

    public function __construct(FileTextExtractor $textExtractor)
    {
        $this->textExtractor = $textExtractor;
    }

    public function generateQuizFromFile(UploadedFile $file, int $numberOfQuestions = 5, array $quizInfo = []): Exam
    {
        // Extract text from file
        $extractedText = $this->textExtractor->extractText($file);
        
        if (empty(trim($extractedText))) {
            throw new Exception('No readable text found in the uploaded file');
        }

        // Generate quiz using AI
        $aiResponse = $this->callAIService($extractedText, $numberOfQuestions);
        
        // Parse AI response
        $parsedQuiz = $this->parseAIResponse($aiResponse);
        
        if (!$parsedQuiz || empty($parsedQuiz['title']) || empty($parsedQuiz['questions'])) {
            throw new Exception('AI failed to generate a valid quiz structure');
        }

        // Create quiz in database with user-provided info
        return $this->createQuizInDatabase($parsedQuiz, $quizInfo);
    }

    private function callAIService(string $content, int $numberOfQuestions): string
    {
        $geminiUrl = config('services.gemini.url');
        $geminiKey = config('services.gemini.key');

        if (empty($geminiUrl) || empty($geminiKey)) {
            throw new Exception('Gemini API configuration is missing');
        }

        $prompt = $this->buildAIPrompt($content, $numberOfQuestions);

        $response = Http::timeout(60)->post($geminiUrl . '?key=' . $geminiKey, [
            'contents' => [
                [
                    'parts' => [
                        ['text' => $prompt],
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

    private function buildAIPrompt(string $content, int $numberOfQuestions): string
    {
        return <<<EOT
You are an AI quiz generator. Based on the provided content, create a comprehensive quiz package with questions and learning metadata.

IMPORTANT: Return ONLY valid JSON, no markdown formatting, no explanations.

Requirements:
- Generate a relevant quiz title based on the content
- Create a brief description (1-2 sentences)
- Generate exactly {$numberOfQuestions} multiple choice questions
- Each question must have 4 options (A, B, C, D)
- Indicate the correct answer by index (0, 1, 2, or 3)
- Provide a short explanation for each correct answer
- Generate learning objectives (3-5 items) based on what students will learn
- Generate prerequisites (2-4 items) - knowledge students should have before taking this quiz
- Generate relevant tags (3-6 items) - keywords related to the content topic

Return this exact JSON structure:
{
  "title": "Quiz title based on content",
  "description": "Brief description of what this quiz covers",
  "learning_objectives": [
    "Students will understand concept A",
    "Students will be able to apply technique B",
    "Students will analyze situation C"
  ],
  "prerequisites": [
    "Basic knowledge of topic X",
    "Understanding of concept Y"
  ],
  "tags": [
    "keyword1",
    "keyword2", 
    "topic-area"
  ],
  "questions": [
    {
      "question": "Question text here?",
      "options": ["Option A", "Option B", "Option C", "Option D"],
      "answer": 0,
      "explanation": "Brief explanation of why this is correct"
    }
  ]
}

Content to analyze:
{$content}
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

        // Validate learning metadata (optional but recommended)
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

            if (!is_array($question['options']) || count($question['options']) !== 4) {
                throw new Exception("Question {$index} must have exactly 4 options");
            }

            if (!is_int($question['answer']) || $question['answer'] < 0 || $question['answer'] > 3) {
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
                'description' => $quizInfo['description'] ?? $quizData['description'] ?? 'AI generated quiz from uploaded file',
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
                    'type' => 'multiple_choice',
                    'points' => 1,
                ]);

                $exam->questions()->attach($question->id, ['order' => $index + 1]);
            }

            return $exam->load('questions');
        });
    }

    private function formatQuestionOptions(array $options): array
    {
        $letters = ['A', 'B', 'C', 'D'];
        $formatted = [];

        foreach ($options as $index => $option) {
            $letter = $letters[$index] ?? chr(65 + $index); // A, B, C, D, E, F...
            $formatted[$letter] = trim($option);
        }

        return $formatted;
    }
}