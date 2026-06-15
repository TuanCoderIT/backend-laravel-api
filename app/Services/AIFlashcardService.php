<?php

namespace App\Services;

use App\Models\Category;
use App\Models\FlashcardSet;
use App\Models\Flashcard;
use Exception;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class AIFlashcardService
{
    private FileTextExtractor $textExtractor;

    public function __construct(FileTextExtractor $textExtractor)
    {
        $this->textExtractor = $textExtractor;
    }

    /**
     * Generate flashcard set from a text prompt
     */
    public function generateFromPrompt(string $prompt, int $numberOfCards = 5, array $setInfo = [], int $userId): FlashcardSet
    {
        $aiResponse = $this->callAIService($prompt, $numberOfCards);
        $parsed = $this->parseAIResponse($aiResponse);

        if (!$parsed || empty($parsed['cards'])) {
            throw new Exception('AI failed to generate a valid flashcard structure');
        }

        return $this->createFlashcardSetInDatabase($parsed, $setInfo, $userId);
    }

    /**
     * Generate flashcard set from an uploaded file
     */
    public function generateFromFile(UploadedFile $file, int $numberOfCards = 5, array $setInfo = [], int $userId): FlashcardSet
    {
        $extractedText = $this->textExtractor->extractText($file);

        if (empty(trim($extractedText))) {
            throw new Exception('Không tìm thấy nội dung văn bản đọc được từ file này.');
        }

        return $this->generateFromPrompt($extractedText, $numberOfCards, $setInfo, $userId);
    }

    /**
     * Call Gemini API to generate flashcards
     */
    private function callAIService(string $content, int $numberOfCards): string
    {
        $geminiUrl = config('services.gemini.url');
        $geminiKey = config('services.gemini.key');

        if (empty($geminiUrl) || empty($geminiKey)) {
            throw new Exception('Cấu hình Gemini API bị thiếu.');
        }

        $prompt = $this->buildAIPrompt($content, $numberOfCards);

        $response = Http::timeout(60)->post($geminiUrl . '?key=' . $geminiKey, [
            'contents' => [
                [
                    'parts' => [
                        ['text' => $prompt],
                    ],
                ],
            ],
            'generationConfig' => [
                'temperature' => 0.5,
                'maxOutputTokens' => 2048,
            ],
        ]);

        if (!$response->successful()) {
            throw new Exception('Dịch vụ AI thất bại: ' . $response->body());
        }

        $aiText = data_get($response->json(), 'candidates.0.content.parts.0.text');

        if (empty($aiText)) {
            throw new Exception('Dịch vụ AI trả về kết quả rỗng.');
        }

        return $aiText;
    }

    /**
     * Build the prompt instructions for AI
     */
    private function buildAIPrompt(string $content, int $numberOfCards): string
    {
        return <<<EOT
Bạn là hệ thống sinh thẻ ghi nhớ (flashcard) thông minh dựa trên nội dung được cung cấp. Hãy tạo đúng định dạng JSON, không định dạng markdown (như ```json), không giải thích thêm.

Yêu cầu:
- Ngôn ngữ: Cùng ngôn ngữ với nội dung được cung cấp (mặc định là Tiếng Việt nếu nội dung hỗn hợp).
- Số lượng thẻ: Đúng {$numberOfCards} thẻ.
- Mỗi thẻ phải có:
  - "term" (Mặt trước - Thuật ngữ, câu hỏi hoặc khái niệm cần nhớ, tối đa 1000 ký tự)
  - "definition" (Mặt sau - Định nghĩa, câu trả lời hoặc giải thích chi tiết cho thuật ngữ ở mặt trước, tối đa 2000 ký tự)
  - "explanation" (Giải thích hoặc ví dụ minh họa thêm nếu cần thiết, có thể rỗng, tối đa 3000 ký tự)

Trả về đúng JSON theo cấu trúc sau:
{
  "cards": [
    {
      "term": "Thuật ngữ hoặc câu hỏi 1",
      "definition": "Định nghĩa hoặc câu trả lời chi tiết cho thuật ngữ 1",
      "explanation": "Giải thích bổ sung hoặc ví dụ cho thẻ 1"
    }
  ]
}

Nội dung để tạo flashcard:
{$content}
EOT;
    }

    /**
     * Clean and parse the AI response
     */
    private function parseAIResponse(string $aiResponse): ?array
    {
        $cleanResponse = trim($aiResponse);

        // Remove markdown code blocks if present
        $cleanResponse = preg_replace('/```json\s*/i', '', $cleanResponse);
        $cleanResponse = preg_replace('/```\s*$/', '', $cleanResponse);
        $cleanResponse = trim($cleanResponse);

        $decoded = json_decode($cleanResponse, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception('AI trả về JSON không hợp lệ: ' . json_last_error_msg());
        }

        if (!isset($decoded['cards']) || !is_array($decoded['cards'])) {
            throw new Exception('JSON trả về từ AI thiếu mảng "cards"');
        }

        // Validate cards structure
        foreach ($decoded['cards'] as $index => $card) {
            if (empty($card['term']) || empty($card['definition'])) {
                throw new Exception("Thẻ số " . ($index + 1) . " bị thiếu thuật ngữ (term) hoặc định nghĩa (definition).");
            }
        }

        return $decoded;
    }

    /**
     * Save generated flashcard set in the database under transaction
     */
    private function createFlashcardSetInDatabase(array $data, array $setInfo, int $userId): FlashcardSet
    {
        return DB::transaction(function () use ($data, $setInfo, $userId) {
            $categoryId = $setInfo['category_id'] ?? Category::first()?->id;

            $flashcardSet = FlashcardSet::create([
                'user_id' => $userId,
                'category_id' => $categoryId,
                'title' => $setInfo['title'] ?? 'Bộ thẻ AI tạo',
                'description' => $setInfo['description'] ?? 'Được tạo tự động bằng AI',
                'visibility' => $setInfo['visibility'] ?? 'private',
                'source_type' => 'ai_generated',
                'status' => $setInfo['status'] ?? 'published',
            ]);

            $cardsToCreate = [];
            foreach ($data['cards'] as $cardData) {
                $cardsToCreate[] = [
                    'front_text' => Str::limit($cardData['term'], 1000, ''),
                    'back_text' => Str::limit($cardData['definition'], 2000, ''),
                    'explanation' => isset($cardData['explanation']) ? Str::limit($cardData['explanation'], 3000, '') : null,
                ];
            }

            $flashcardSet->flashcards()->createMany($cardsToCreate);

            return $flashcardSet;
        });
    }
}
