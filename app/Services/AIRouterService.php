<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AIRouterService
{
    private string $apiUrl;
    private string $apiKey;

    public function __construct()
    {
        $this->apiUrl = config('services.gemini.url');
        $this->apiKey = config('services.gemini.key');
    }

    public function analyzeIntent(string $message, ?string $contextType = null, ?int $contextId = null): array
    {
        try {
            $prompt = $this->buildRouterPrompt($message, $contextType, $contextId);
            $response = $this->callGeminiAPI($prompt);
            
            // Clean response - remove markdown formatting
            $cleanResponse = $this->cleanJsonResponse($response);
            
            // Parse JSON response từ AI
            $intentData = json_decode($cleanResponse, true);
            
            if (!$intentData || !isset($intentData['action'])) {
                Log::warning('AI Router: Invalid response format', [
                    'raw_response' => $response,
                    'clean_response' => $cleanResponse,
                    'json_error' => json_last_error_msg()
                ]);
                throw new \Exception('Invalid AI router response format');
            }

            return [
                'success' => true,
                'intent' => $intentData
            ];
            
        } catch (\Exception $e) {
            Log::error('AI Router Error: ' . $e->getMessage());
            
            // Fallback: phân tích đơn giản dựa trên keywords
            $fallbackIntent = $this->getFallbackIntent($message, $contextType, $contextId);
            
            return [
                'success' => true,
                'intent' => $fallbackIntent
            ];
        }
    }

    private function cleanJsonResponse(string $response): string
    {
        // Remove markdown code blocks
        $response = preg_replace('/```json\s*/', '', $response);
        $response = preg_replace('/```\s*$/', '', $response);
        
        // Remove extra whitespace and newlines
        $response = trim($response);
        
        // Find JSON object
        $start = strpos($response, '{');
        $end = strrpos($response, '}');
        
        if ($start !== false && $end !== false && $end > $start) {
            return substr($response, $start, $end - $start + 1);
        }
        
        return $response;
    }

    private function getFallbackIntent(string $message, ?string $contextType, ?int $contextId): array
    {
        $message = strtolower($message);
        
        // Check for off-topic keywords first
        $offTopicKeywords = [
            'phim', 'movie', 'xem phim', 'cinema', 'netflix',
            'ăn', 'food', 'món ăn', 'nhà hàng', 'quán ăn',
            'mua sắm', 'shopping', 'mua', 'bán',
            'du lịch', 'travel', 'đi chơi',
            'thể thao', 'sport', 'bóng đá', 'football',
            'âm nhạc', 'music', 'bài hát', 'ca sĩ',
            'game', 'chơi game', 'gaming',
            'tình yêu', 'love', 'bạn gái', 'bạn trai',
            'thời tiết', 'weather', 'trời',
            'tin tức', 'news', 'chính trị'
        ];
        
        foreach ($offTopicKeywords as $keyword) {
            if (strpos($message, $keyword) !== false) {
                return [
                    'action' => 'off_topic',
                    'params' => ['message' => $message]
                ];
            }
        }
        
        // Educational keywords
        if (strpos($message, 'khóa học') !== false || strpos($message, 'course') !== false) {
            if (strpos($message, 'tìm') !== false || strpos($message, 'có gì') !== false) {
                return [
                    'action' => 'course_search',
                    'params' => ['query' => '']
                ];
            }
            if ($contextId && $contextType === 'course') {
                return [
                    'action' => 'course_info',
                    'params' => ['course_id' => $contextId]
                ];
            }
        }
        
        if (strpos($message, 'đề thi') !== false || strpos($message, 'exam') !== false || strpos($message, 'quiz') !== false) {
            if (strpos($message, 'tìm') !== false || strpos($message, 'có gì') !== false) {
                return [
                    'action' => 'exam_search',
                    'params' => ['query' => '']
                ];
            }
            if ($contextId && $contextType === 'exam') {
                return [
                    'action' => 'exam_info',
                    'params' => ['exam_id' => $contextId]
                ];
            }
        }
        
        if (strpos($message, 'tiến độ') !== false || strpos($message, 'progress') !== false) {
            return [
                'action' => 'learning_progress',
                'params' => $contextId ? ['course_id' => $contextId] : []
            ];
        }
        
        if (strpos($message, 'gợi ý') !== false || strpos($message, 'recommend') !== false) {
            return [
                'action' => 'study_recommendation',
                'params' => []
            ];
        }
        
        // Check for educational topics
        $educationalKeywords = [
            'học', 'learning', 'study', 'giải thích', 'explain',
            'programming', 'lập trình', 'code', 'coding',
            'toán', 'math', 'mathematics', 'tính toán',
            'khoa học', 'science', 'vật lý', 'physics',
            'hóa học', 'chemistry', 'sinh học', 'biology',
            'lịch sử', 'history', 'địa lý', 'geography',
            'ngôn ngữ', 'language', 'tiếng anh', 'english',
            'kiến thức', 'knowledge', 'hiểu biết'
        ];
        
        foreach ($educationalKeywords as $keyword) {
            if (strpos($message, $keyword) !== false) {
                return [
                    'action' => 'general_chat',
                    'params' => ['message' => $message]
                ];
            }
        }
        
        // Default to off_topic for unclear messages
        return [
            'action' => 'off_topic',
            'params' => ['message' => $message]
        ];
    }

    private function buildRouterPrompt(string $message, ?string $contextType, ?int $contextId): string
    {
        $contextInfo = '';
        if ($contextType && $contextId) {
            $contextInfo = "\nContext: User đang xem {$contextType} ID {$contextId}";
        }

        return "You are an AI Router for an EDUCATION SYSTEM. Analyze the user's question and return ONLY a JSON object with action and params.

AVAILABLE ACTIONS:
1. course_info - Ask about specific course info
   Params: { \"course_id\": number }
   
2. course_search - Search for courses  
   Params: { \"query\": string, \"category\": string }
   
3. exam_info - Ask about specific exam info
   Params: { \"exam_id\": number }
   
4. exam_search - Search for exams
   Params: { \"query\": string, \"difficulty\": string }
   
5. learning_progress - Ask about learning progress
   Params: { \"course_id\": number }
   
6. study_recommendation - Ask for study recommendations
   Params: { \"level\": string, \"topic\": string }
   
7. general_chat - Educational questions, concept explanations
   Params: { \"message\": string }
   
8. off_topic - Non-educational questions (movies, food, personal life, etc.)
   Params: { \"message\": string }

IMPORTANT RULES:
- This is an EDUCATION SYSTEM - only handle learning-related questions
- If question is about movies, entertainment, food, personal life, shopping, etc. → use \"off_topic\"
- If question is about programming, math, science, learning concepts → use \"general_chat\"
- If user asks about courses/exams in general → use course_search/exam_search
- Return ONLY valid JSON, no markdown, no explanations

EDUCATION TOPICS: courses, exams, learning, programming, math, science, study tips, homework help, concepts explanation
NON-EDUCATION TOPICS: movies, music, food, shopping, personal life, entertainment, sports, travel

{$contextInfo}

User message: \"{$message}\"

JSON:";
    }

    private function callGeminiAPI(string $prompt): string
    {
        $response = Http::timeout(30)
            ->withHeaders(['Content-Type' => 'application/json'])
            ->post($this->apiUrl . '?key=' . $this->apiKey, [
                'contents' => [
                    [
                        'parts' => [
                            ['text' => $prompt]
                        ]
                    ]
                ]
            ]);

        if (!$response->successful()) {
            throw new \Exception('Gemini API Error: ' . $response->body());
        }

        $data = $response->json();
        
        if (!isset($data['candidates'][0]['content']['parts'][0]['text'])) {
            throw new \Exception('Invalid Gemini API response format');
        }

        return trim($data['candidates'][0]['content']['parts'][0]['text']);
    }
}