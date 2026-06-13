<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Achievement;
use App\Models\FlashcardProgress;
use App\Models\FlashcardSet;
use App\Models\Result;
use App\Models\User;
use App\Models\XpLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GamificationController extends Controller
{
    public function achievements(Request $request): JsonResponse
    {
        $user = $request->user();

        $unlockedAchievements = $user->userAchievements()
            ->with('achievement')
            ->get()
            ->keyBy('achievement_id');

        $progressValues = $this->progressValues($user);

        $achievements = Achievement::where('is_active', true)
            ->orderBy('type')
            ->orderBy('target_value')
            ->get()
            ->map(function (Achievement $achievement) use ($unlockedAchievements, $progressValues) {
                $userAchievement = $unlockedAchievements->get($achievement->id);
                $progress = (int) ($progressValues[$achievement->type] ?? 0);
                $targetValue = max(1, (int) $achievement->target_value);

                return [
                    'id' => $achievement->id,
                    'code' => $achievement->code,
                    'name' => $achievement->name,
                    'description' => $achievement->description,
                    'icon' => $achievement->icon,
                    'type' => $achievement->type,
                    'rarity' => $achievement->rarity,
                    'target_value' => $targetValue,
                    'xp_reward' => (int) $achievement->xp_reward,
                    'token_reward' => (int) $achievement->token_reward,
                    'reward_title' => $achievement->reward_title,
                    'reward_trophy' => $achievement->reward_trophy,
                    'conditions' => $achievement->conditions,
                    'is_active' => (bool) $achievement->is_active,
                    'unlocked' => $userAchievement !== null,
                    'unlocked_at' => optional($userAchievement?->unlocked_at)->toDateTimeString(),
                    'progress' => min($progress, $targetValue),
                    'progress_percent' => min(100, (int) floor(($progress / $targetValue) * 100)),
                    'created_at' => optional($achievement->created_at)->toDateTimeString(),
                    'updated_at' => optional($achievement->updated_at)->toDateTimeString(),
                ];
            })
            ->values();

        return response()->json([
            'success' => true,
            'data' => $achievements,
        ]);
    }

    public function summary(Request $request): JsonResponse
    {
        $user = $request->user();
        $progressValues = $this->progressValues($user);
        $achievementsTotal = Achievement::where('is_active', true)->count();
        $achievementsUnlocked = $user->userAchievements()->count();
        $xp = (int) $user->xp;

        return response()->json([
            'success' => true,
            'data' => [
                'xp' => $xp,
                'level' => $this->levelFromXp($xp),
                'current_streak' => (int) $user->current_streak,
                'longest_streak' => (int) $user->longest_streak,
                'streak_freezes' => (int) $user->streak_freezes,
                'last_activity_at' => optional($user->last_activity_at)->toDateTimeString(),
                'achievements_unlocked' => $achievementsUnlocked,
                'achievements_total' => $achievementsTotal,
                'quiz_completed' => (int) $progressValues['quiz_completed'],
                'perfect_scores' => (int) $progressValues['perfect_score'],
                'flashcards_reviewed' => (int) $progressValues['flashcard_review'],
                'wrong_answer_flashcard_sets' => (int) $progressValues['wrong_answer_flashcards'],
            ],
        ]);
    }

    public function xpLogs(Request $request): JsonResponse
    {
        $limit = min((int) $request->query('limit', 50), 100);

        $logs = XpLog::where('user_id', $request->user()->id)
            ->latest()
            ->limit($limit)
            ->get()
            ->map(function (XpLog $log) {
                return [
                    'id' => $log->id,
                    'amount' => (int) $log->amount,
                    'source_type' => $log->source_type,
                    'source_id' => $log->source_id,
                    'description' => $log->description,
                    'created_at' => optional($log->created_at)->toDateTimeString(),
                ];
            });

        return response()->json([
            'success' => true,
            'data' => $logs,
        ]);
    }

    private function progressValues(User $user): array
    {
        return [
            'quiz_completed' => Result::where('user_id', $user->id)->count(),
            'perfect_score' => Result::where('user_id', $user->id)
                ->where('percentage', 100)
                ->count(),
            'streak' => (int) $user->current_streak,
            'flashcard_review' => (int) FlashcardProgress::where('user_id', $user->id)
                ->sum('review_count'),
            'wrong_answer_flashcards' => FlashcardSet::where('user_id', $user->id)
                ->where('source_type', 'quiz_wrong_answers')
                ->count(),
        ];
    }

    private function levelFromXp(int $xp): int
    {
        return intdiv($xp, 500) + 1;
    }
}
