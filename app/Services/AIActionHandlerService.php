<?php

namespace App\Services;

use App\Models\Course;
use App\Models\Exam;
use App\Models\CourseProgress;
use Illuminate\Support\Facades\Auth;

class AIActionHandlerService
{
    public function handleAction(string $action, array $params, int $userId): array
    {
        try {
            return match ($action) {
                'course_info' => $this->handleCourseInfo($params),
                'course_search' => $this->handleCourseSearch($params),
                'exam_info' => $this->handleExamInfo($params),
                'exam_search' => $this->handleExamSearch($params),
                'learning_progress' => $this->handleLearningProgress($params, $userId),
                'study_recommendation' => $this->handleStudyRecommendation($params, $userId),
                'general_chat' => $this->handleGeneralChat($params),
                'off_topic' => $this->handleOffTopic($params),
                default => $this->handleOffTopic($params)
            };
        } catch (\Exception $e) {
            return [
                'success' => false,
                'data' => null,
                'message' => 'Có lỗi xảy ra khi xử lý yêu cầu: ' . $e->getMessage()
            ];
        }
    }

    private function handleCourseInfo(array $params): array
    {
        $courseId = $params['course_id'] ?? null;
        
        if (!$courseId) {
            return [
                'success' => false,
                'data' => null,
                'message' => 'Vui lòng cung cấp ID khóa học'
            ];
        }

        $course = Course::with(['category', 'user'])
            ->withCount(['chapters', 'lessons'])
            ->find($courseId);

        if (!$course) {
            return [
                'success' => false,
                'data' => null,
                'message' => 'Không tìm thấy khóa học'
            ];
        }

        return [
            'success' => true,
            'data' => [
                'course' => [
                    'id' => $course->id,
                    'title' => $course->title,
                    'description' => $course->description,
                    'category' => $course->category->name ?? 'N/A',
                    'instructor' => $course->user->name ?? 'N/A',
                    'chapters_count' => $course->chapters_count,
                    'lessons_count' => $course->lessons_count,
                    'difficulty' => $course->difficulty ?? 'N/A',
                    'duration' => $course->duration ?? 'N/A'
                ]
            ],
            'message' => 'Thông tin khóa học'
        ];
    }

    private function handleCourseSearch(array $params): array
    {
        $query = $params['query'] ?? '';
        $category = $params['category'] ?? null;

        $courses = Course::with(['category', 'user'])
            ->withCount('lessons')
            ->when($query, function ($q) use ($query) {
                $q->where('title', 'like', "%{$query}%")
                  ->orWhere('description', 'like', "%{$query}%");
            })
            ->when($category, function ($q) use ($category) {
                $q->whereHas('category', function ($cat) use ($category) {
                    $cat->where('name', 'like', "%{$category}%");
                });
            })
            ->limit(5)
            ->get();

        return [
            'success' => true,
            'data' => [
                'courses' => $courses->map(function ($course) {
                    return [
                        'id' => $course->id,
                        'title' => mb_convert_encoding($course->title ?? '', 'UTF-8', 'UTF-8'),
                        'description' => mb_convert_encoding(substr($course->description ?? '', 0, 100), 'UTF-8', 'UTF-8') . '...',
                        'category' => mb_convert_encoding($course->category->name ?? 'N/A', 'UTF-8', 'UTF-8'),
                        'instructor' => mb_convert_encoding($course->user->name ?? 'N/A', 'UTF-8', 'UTF-8'),
                        'lessons_count' => $course->lessons_count ?? 0
                    ];
                })->toArray(),
                'total' => $courses->count()
            ],
            'message' => "Tìm thấy {$courses->count()} khóa học"
        ];
    }

    private function handleExamInfo(array $params): array
    {
        $examId = $params['exam_id'] ?? null;
        
        if (!$examId) {
            return [
                'success' => false,
                'data' => null,
                'message' => 'Vui lòng cung cấp ID đề thi'
            ];
        }

        $exam = Exam::with('category')
            ->withCount('questions')
            ->find($examId);

        if (!$exam) {
            return [
                'success' => false,
                'data' => null,
                'message' => 'Không tìm thấy đề thi'
            ];
        }

        return [
            'success' => true,
            'data' => [
                'exam' => [
                    'id' => $exam->id,
                    'title' => $exam->title,
                    'description' => $exam->description,
                    'category' => $exam->category->name ?? 'N/A',
                    'difficulty' => $exam->difficulty,
                    'duration' => $exam->duration,
                    'questions_count' => $exam->questions_count,
                    'passing_score' => $exam->passing_score
                ]
            ],
            'message' => 'Thông tin đề thi'
        ];
    }

    private function handleExamSearch(array $params): array
    {
        $query = $params['query'] ?? '';
        $difficulty = $params['difficulty'] ?? null;

        $exams = Exam::with('category')
            ->withCount('questions')
            ->when($query, function ($q) use ($query) {
                $q->where('title', 'like', "%{$query}%")
                  ->orWhere('description', 'like', "%{$query}%");
            })
            ->when($difficulty, function ($q) use ($difficulty) {
                $q->where('difficulty', $difficulty);
            })
            ->limit(5)
            ->get();

        return [
            'success' => true,
            'data' => [
                'exams' => $exams->map(function ($exam) {
                    return [
                        'id' => $exam->id,
                        'title' => $exam->title,
                        'description' => substr($exam->description, 0, 100) . '...',
                        'category' => $exam->category->name ?? 'N/A',
                        'difficulty' => $exam->difficulty,
                        'questions_count' => $exam->questions_count
                    ];
                })->toArray(),
                'total' => $exams->count()
            ],
            'message' => "Tìm thấy {$exams->count()} đề thi"
        ];
    }

    private function handleLearningProgress(array $params, int $userId): array
    {
        $courseId = $params['course_id'] ?? null;

        if ($courseId) {
            // Progress của 1 khóa học cụ thể
            $progress = CourseProgress::where('user_id', $userId)
                ->where('course_id', $courseId)
                ->with('course')
                ->first();

            if (!$progress) {
                return [
                    'success' => true,
                    'data' => ['progress' => 0, 'course_id' => $courseId],
                    'message' => 'Bạn chưa bắt đầu khóa học này'
                ];
            }

            return [
                'success' => true,
                'data' => [
                    'progress' => $progress->progress_percentage,
                    'course' => $progress->course->title,
                    'completed_lessons' => $progress->completed_lessons_count ?? 0
                ],
                'message' => 'Tiến độ học tập của bạn'
            ];
        } else {
            // Tổng quan tiến độ tất cả khóa học
            $progresses = CourseProgress::where('user_id', $userId)
                ->with('course')
                ->get();

            return [
                'success' => true,
                'data' => [
                    'total_courses' => $progresses->count(),
                    'avg_progress' => $progresses->avg('progress_percentage') ?? 0,
                    'courses' => $progresses->map(function ($p) {
                        return [
                            'course' => $p->course->title,
                            'progress' => $p->progress_percentage
                        ];
                    })->toArray()
                ],
                'message' => 'Tổng quan tiến độ học tập'
            ];
        }
    }

    private function handleStudyRecommendation(array $params, int $userId): array
    {
        $level = $params['level'] ?? 'beginner';
        $topic = $params['topic'] ?? null;

        $courses = Course::with('category')
            ->when($topic, function ($q) use ($topic) {
                $q->where('title', 'like', "%{$topic}%")
                  ->orWhereHas('category', function ($cat) use ($topic) {
                      $cat->where('name', 'like', "%{$topic}%");
                  });
            })
            ->when($level, function ($q) use ($level) {
                $q->where('difficulty', $level);
            })
            ->limit(3)
            ->get();

        return [
            'success' => true,
            'data' => [
                'recommendations' => $courses->map(function ($course) {
                    return [
                        'id' => $course->id,
                        'title' => $course->title,
                        'category' => $course->category->name ?? 'N/A',
                        'difficulty' => $course->difficulty
                    ];
                })->toArray()
            ],
            'message' => 'Gợi ý khóa học phù hợp với bạn'
        ];
    }

    private function handleGeneralChat(array $params): array
    {
        $message = $params['message'] ?? '';

        return [
            'success' => true,
            'data' => [
                'type' => 'general',
                'original_message' => $message
            ],
            'message' => 'Câu hỏi chung cần AI trả lời'
        ];
    }

    private function handleOffTopic(array $params): array
    {
        return [
            'success' => true,
            'data' => [
                'type' => 'off_topic',
                'message' => 'Tôi là trợ lý học tập AI, chỉ có thể hỗ trợ các câu hỏi về học tập.'
            ],
            'message' => 'Câu hỏi ngoài phạm vi hỗ trợ'
        ];
    }
}