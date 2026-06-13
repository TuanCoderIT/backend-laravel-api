<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Result;
use App\Models\ResultAnswer;
use App\Models\Exam;
use App\Services\GamificationService;
use App\Services\AchievementService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ResultController extends Controller
{
    public function __construct(
        private GamificationService $gamificationService,
        private AchievementService $achievementService
    ) {
    }

    public function index(Request $request)
    {
        $userId = $request->user()?->id ?? $request->query('user_id');

        if (!$userId) {
            return response()->json(['error' => 'User not authenticated'], 401);
        }

        $results = Result::with('exam')
            ->where('user_id', $userId)
            ->orderBy('completed_at', 'desc')
            ->get();

        return response()->json($results);
    }


    public function store(Request $request)
    {
        $validated = $request->validate([
            'exam_id' => 'required|exists:exams,id',
            'answers' => 'required|array',
            'answers.*.question_id' => 'required|exists:questions,id',
            'answers.*.user_answer' => 'nullable|string',
            'time_spent' => 'required|integer',
        ]);

        $userId = $request->user()->id;
        $examId = $validated['exam_id'];

        $exam = Exam::with('questions')->findOrFail($examId);
        $questions = $exam->questions->keyBy('id');
        $submittedQuestionIds = collect($validated['answers'])
            ->pluck('question_id')
            ->unique()
            ->values();
        $invalidQuestionIds = $submittedQuestionIds
            ->diff($questions->keys())
            ->values();

        if ($invalidQuestionIds->isNotEmpty()) {
            throw ValidationException::withMessages([
                'answers' => [
                    'Một số câu hỏi không thuộc bài quiz này: ' . $invalidQuestionIds->implode(', '),
                ],
            ]);
        }

        $totalQuestions = $questions->count();
        $score = 0;
        $resultAnswersData = [];

        foreach ($validated['answers'] as $answerData) {
            $questionId = $answerData['question_id'];
            $userAnswer = $answerData['user_answer'] ?? null;
            $question = $questions->get($questionId);

            $correctAnswer = $question->answer;
            $isCorrect = $userAnswer === $correctAnswer;
            $points = $isCorrect ? ($question->points ?? 1) : 0;

            if ($isCorrect) {
                $score++;
            }

            $resultAnswersData[] = [
                'user_id' => $userId,
                'exam_id' => $examId,
                'question_id' => $questionId,
                'user_answer' => $userAnswer,
                'correct_answer' => $correctAnswer,
                'is_correct' => $isCorrect,
                'points' => $points,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        $percentage = $totalQuestions > 0 ? round(($score / $totalQuestions) * 100) : 0;

        $result = DB::transaction(function () use ($userId, $examId, $score, $totalQuestions, $percentage, $validated, $resultAnswersData) {
            $result = Result::create([
                'user_id' => $userId,
                'exam_id' => $examId,
                'score' => $score,
                'total' => $totalQuestions,
                'percentage' => $percentage,
                'time_spent' => $validated['time_spent'],
                'completed_at' => now(),
            ]);

            foreach ($resultAnswersData as &$data) {
                $data['result_id'] = $result->id;
            }
            unset($data);

            ResultAnswer::insert($resultAnswersData);

            return $result;
        });

        $this->gamificationService->reward($request->user(), 50, 'quiz_completed', $result->id, 'Hoàn thành quiz');

        $unlockedAchievements = $this->achievementService->checkAndUnlock($request->user(), 'quiz_submitted', ['result' => $result]);

        return response()->json([
            'message' => 'Result saved successfully',
            'result' => $result,
            'unlocked_achievements' => $unlockedAchievements,
        ], 201);
    }
}
