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
            
            $cleanResponse = $this->cleanJsonResponse($response);

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

//     private function buildRouterPrompt(string $message, ?string $contextType, ?int $contextId): string
//     {
//         $contextInfo = '';
//         if ($contextType && $contextId) {
//             $contextInfo = "\nContext: User đang xem {$contextType} ID {$contextId}";
//         }

//         return "You are an AI Router for an EDUCATION SYSTEM. Analyze the user's question and return ONLY a JSON object with action and params.

// AVAILABLE ACTIONS:
// 1. course_info - Ask about specific course info
//    Params: { \"course_id\": number }
   
// 2. course_search - Search for courses  
//    Params: { \"query\": string, \"category\": string }
   
// 3. exam_info - Ask about specific exam info
//    Params: { \"exam_id\": number }
   
// 4. exam_search - Search for exams
//    Params: { \"query\": string, \"difficulty\": string }
   
// 5. learning_progress - Ask about learning progress
//    Params: { \"course_id\": number }
   
// 6. study_recommendation - Ask for study recommendations
//    Params: { \"level\": string, \"topic\": string }
   
// 7. general_chat - Educational questions, concept explanations
//    Params: { \"message\": string }
   
// 8. off_topic - Non-educational questions (movies, food, personal life, etc.)
//    Params: { \"message\": string }

// IMPORTANT RULES:
// - This is an EDUCATION SYSTEM - only handle learning-related questions
// - If question is about movies, entertainment, food, personal life, shopping, etc. → use \"off_topic\"
// - If question is about programming, math, science, learning concepts → use \"general_chat\"
// - If user asks about courses/exams in general → use course_search/exam_search
// - Return ONLY valid JSON, no markdown, no explanations

// EDUCATION TOPICS: courses, exams, learning, programming, math, science, study tips, homework help, concepts explanation
// NON-EDUCATION TOPICS: movies, music, food, shopping, personal life, entertainment, sports, travel

// {$contextInfo}

// User message: \"{$message}\"

// JSON:";
//     }

private function buildRouterPrompt(string $message, ?string $contextType, ?int $contextId): string
{
    $contextInfo = $contextType && $contextId
        ? "User is viewing {$contextType} ID {$contextId}"
        : "No specific context";

    return <<<PROMPT
You are an AI INTENT ROUTER for an EDUCATIONAL PLATFORM.

Your role:
- Decide WHAT the user wants to do
- Choose ONE best action
- Extract minimal parameters
- Be flexible with vague or broad questions

IMPORTANT:
- Prefer SEARCH actions over GENERAL_CHAT
- If the user is asking for resources, suggestions, or availability → use *_search
- Do NOT answer the user
- Do NOT explain your choice
- Return ONLY valid JSON

AVAILABLE ACTIONS:

1. course_search  
User wants to find or browse courses  
Examples:
- "mình nên học java"
- "có khóa học nào về oop không"
- "học lập trình bắt đầu từ đâu"

Params:
{ "query"?: string }

---

2. exam_search  
User wants to find quizzes or exams  
Examples:
- "quiz an toàn thông tin"
- "có đề thi java không"

Params:
{ "query"?: string }

---

3. exam_info  
User asks about the exam they are currently viewing  
ONLY if contextType = exam

Params:
{ "exam_id": number }

---

4. study_group_search  
User wants to find or join study groups  
Examples:
- "có nhóm học tập nào không"
- "nhóm học java"
- "học nhóm an toàn thông tin"

Params:
{ "topic"?: string }

---

5. material_search  
User wants learning materials or documents  
Examples:
- "có tài liệu java không"
- "pdf an toàn thông tin"
- "slide oop"

Params:
{ "query"?: string }

---

6. learning_progress  
User asks about their learning progress

Params:
{ "course_id"?: number }

---

7. study_recommendation  
User wants advice, roadmap, or learning direction  
Examples:
- "nên học gì tiếp theo"
- "lộ trình học java"
- "mình mới học thì nên bắt đầu từ đâu"

Params:
{ "topic"?: string }

---

8. general_chat  
Pure knowledge or concept explanation NOT tied to platform data  
Examples:
- "java là gì"
- "oop là gì"

Params:
{ "message": string }

---

9. off_topic  
Non-educational questions

Params:
{ "message": string }

Rules:
- If the question is broad but related to learning → DO NOT use off_topic
- If unsure between search and chat → choose SEARCH
- Never invent data

Context:
{$contextInfo}

User message:
"{$message}"

Return JSON only.
PROMPT;
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