<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AIFormatterService
{
    private string $apiUrl;
    private string $apiKey;

    public function __construct()
    {
        $this->apiUrl = config('services.gemini.url');
        $this->apiKey = config('services.gemini.key');
    }

    public function formatResponse(string $action, array $data, string $originalMessage): string
    {
        try {
            // Chỉ format cho general_chat, các action khác trả raw data
            if ($action !== 'general_chat') {
                return $this->formatStructuredData($action, $data);
            }

            $prompt = $this->buildFormatterPrompt($originalMessage);
            return $this->callGeminiAPI($prompt);
            
        } catch (\Exception $e) {
            Log::error('AI Formatter Error: ' . $e->getMessage());
            return $this->formatStructuredData($action, $data);
        }
    }

    private function formatStructuredData(string $action, array $data): string
    {
        return match ($action) {
            'course_info' => $this->formatCourseInfo($data),
            'course_search' => $this->formatCourseSearch($data),
            'exam_info' => $this->formatExamInfo($data),
            'exam_search' => $this->formatExamSearch($data),
            'learning_progress' => $this->formatLearningProgress($data),
            'study_recommendation' => $this->formatStudyRecommendation($data),
            'off_topic' => $this->formatOffTopic($data),
            default => 'Tôi đã xử lý yêu cầu của bạn.'
        };
    }

    private function formatCourseInfo(array $data): string
    {
        if (!isset($data['course'])) {
            return 'Không tìm thấy thông tin khóa học.';
        }

        $course = $data['course'];
        return "📚 **{$course['title']}**\n\n" .
               "📝 Mô tả: {$course['description']}\n" .
               "🏷️ Danh mục: {$course['category']}\n" .
               "👨‍🏫 Giảng viên: {$course['instructor']}\n" .
               "📖 Số chương: {$course['chapters_count']}\n" .
               "📑 Số bài học: {$course['lessons_count']}\n" .
               "⭐ Độ khó: {$course['difficulty']}\n" .
               "⏱️ Thời lượng: {$course['duration']}";
    }

    private function formatCourseSearch(array $data): string
    {
        if (empty($data['courses'])) {
            return 'Không tìm thấy khóa học nào phù hợp.';
        }

        $result = "🔍 Tìm thấy {$data['total']} khóa học:\n\n";
        foreach ($data['courses'] as $course) {
            $title = mb_convert_encoding($course['title'] ?? '', 'UTF-8', 'UTF-8');
            $category = mb_convert_encoding($course['category'] ?? 'N/A', 'UTF-8', 'UTF-8');
            $instructor = mb_convert_encoding($course['instructor'] ?? 'N/A', 'UTF-8', 'UTF-8');
            $description = mb_convert_encoding($course['description'] ?? '', 'UTF-8', 'UTF-8');
            
            $result .= "📚 **{$title}**\n";
            $result .= "   🏷️ {$category} | 👨‍🏫 {$instructor} | 📑 {$course['lessons_count']} bài\n";
            $result .= "   📝 {$description}\n\n";
        }

        return $result;
    }

    private function formatExamInfo(array $data): string
    {
        if (!isset($data['exam'])) {
            return 'Không tìm thấy thông tin đề thi.';
        }

        $exam = $data['exam'];
        return "📝 **{$exam['title']}**\n\n" .
               "📄 Mô tả: {$exam['description']}\n" .
               "🏷️ Danh mục: {$exam['category']}\n" .
               "⭐ Độ khó: {$exam['difficulty']}\n" .
               "❓ Số câu hỏi: {$exam['questions_count']}\n" .
               "⏱️ Thời gian: {$exam['duration']} phút\n" .
               "🎯 Điểm đạt: {$exam['passing_score']}%";
    }

    private function formatExamSearch(array $data): string
    {
        if (empty($data['exams'])) {
            return 'Không tìm thấy đề thi nào phù hợp.';
        }

        $result = "🔍 Tìm thấy {$data['total']} đề thi:\n\n";
        foreach ($data['exams'] as $exam) {
            $result .= "📝 **{$exam['title']}**\n";
            $result .= "   🏷️ {$exam['category']} | ⭐ {$exam['difficulty']} | ❓ {$exam['questions_count']} câu\n";
            $result .= "   📄 {$exam['description']}\n\n";
        }

        return $result;
    }

    private function formatLearningProgress(array $data): string
    {
        if (isset($data['courses'])) {
            // Multiple courses progress
            $result = "📊 **Tổng quan tiến độ học tập**\n\n";
            $result .= "📚 Tổng khóa học: {$data['total_courses']}\n";
            $result .= "📈 Tiến độ trung bình: " . round($data['avg_progress'], 1) . "%\n\n";
            
            if (!empty($data['courses'])) {
                $result .= "**Chi tiết từng khóa:**\n";
                foreach ($data['courses'] as $course) {
                    $result .= "• {$course['course']}: {$course['progress']}%\n";
                }
            }
            
            return $result;
        } else {
            // Single course progress
            return "📊 **Tiến độ khóa học**\n\n" .
                   "📚 Khóa học: {$data['course']}\n" .
                   "📈 Tiến độ: {$data['progress']}%\n" .
                   "✅ Bài đã hoàn thành: {$data['completed_lessons']}";
        }
    }

    private function formatStudyRecommendation(array $data): string
    {
        if (empty($data['recommendations'])) {
            return 'Hiện tại chưa có gợi ý phù hợp. Hãy thử tìm kiếm với từ khóa khác.';
        }

        $result = "💡 **Gợi ý khóa học cho bạn:**\n\n";
        foreach ($data['recommendations'] as $course) {
            $result .= "📚 **{$course['title']}**\n";
            $result .= "   🏷️ {$course['category']} | ⭐ {$course['difficulty']}\n\n";
        }

        return $result;
    }

    private function formatOffTopic(array $data): string
    {
        return "🤖 Xin lỗi! Tôi là trợ lý học tập AI, chỉ có thể hỗ trợ các câu hỏi về:\n\n" .
               "📚 **Khóa học** - Tìm kiếm, thông tin chi tiết\n" .
               "📝 **Đề thi & Quiz** - Tìm kiếm, thông tin chi tiết\n" .
               "📊 **Tiến độ học tập** - Theo dõi progress\n" .
               "💡 **Gợi ý học tập** - Khóa học phù hợp\n" .
               "🧠 **Giải thích khái niệm** - Lập trình, toán, khoa học\n\n" .
               "Hãy hỏi tôi về những chủ đề học tập nhé! 😊";
    }

    private function buildFormatterPrompt(string $originalMessage): string
    {
        return "Bạn là trợ lý học tập AI chuyên nghiệp. Trả lời câu hỏi học tập sau một cách hữu ích và dễ hiểu.

QUAN TRỌNG:
- CHỈ trả lời các câu hỏi về học tập, giáo dục, lập trình, toán, khoa học
- KHÔNG trả lời về: phim ảnh, ăn uống, mua sắm, giải trí, đời sống cá nhân
- Nếu câu hỏi không liên quan đến học tập, hãy từ chối một cách lịch sự

Quy tắc trả lời:
- Trả lời bằng tiếng Việt
- Giải thích rõ ràng, dễ hiểu
- Khuyến khích học tập
- Nếu không biết chắc, hãy thành thật nói không biết
- Đưa ra ví dụ cụ thể khi có thể

Câu hỏi: {$originalMessage}

Trả lời:";
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