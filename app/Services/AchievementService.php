<?php

namespace App\Services;

use App\Models\User;
use App\Models\Achievement;
use App\Models\UserAchievement;
use App\Models\Result;
use App\Models\FlashcardProgress;
use App\Models\FlashcardSet;
use Illuminate\Support\Facades\DB;

class AchievementService
{
    public function __construct(private GamificationService $gamificationService) {}

    /**
     * Unlock an achievement for a user if not already unlocked.
     */
    public function unlock(User $user, string $code): ?Achievement
    {
        // Find active achievement by code
        $achievement = Achievement::where('code', $code)
            ->where('is_active', true)
            ->first();

        if (!$achievement) {
            return null;
        }

        // Check if already unlocked
        $alreadyUnlocked = UserAchievement::where('user_id', $user->id)
            ->where('achievement_id', $achievement->id)
            ->exists();

        if ($alreadyUnlocked) {
            return null;
        }

        // Lock in DB transaction
        return DB::transaction(function () use ($user, $achievement) {
            // Re-check inside transaction to prevent race conditions
            $alreadyUnlocked = UserAchievement::where('user_id', $user->id)
                ->where('achievement_id', $achievement->id)
                ->exists();

            if ($alreadyUnlocked) {
                return null;
            }

            // Unlock
            UserAchievement::create([
                'user_id' => $user->id,
                'achievement_id' => $achievement->id,
                'unlocked_at' => now(),
            ]);

            // Reward XP if present
            if ($achievement->xp_reward > 0) {
                // Call GamificationService to award XP
                $this->gamificationService->addXp(
                    $user,
                    $achievement->xp_reward,
                    'achievement_reward',
                    $achievement->id,
                    "Mở khóa danh hiệu: {$achievement->name}"
                );
            }

            return $achievement;
        });
    }

    /**
     * Check and unlock achievements based on triggers.
     * Returns list of newly unlocked achievements.
     */
    public function checkAndUnlock(User $user, string $triggerType, array $context = []): array
    {
        $user->refresh();

        $unlocked = [];

        // Determine types to check based on trigger
        $typesToCheck = ['streak']; // Always check streak since it can update during any learning action

        if ($triggerType === 'quiz_submitted') {
            $typesToCheck[] = 'quiz_completed';
            $typesToCheck[] = 'perfect_score';
        } elseif ($triggerType === 'flashcard_reviewed') {
            $typesToCheck[] = 'flashcard_review';
        } elseif ($triggerType === 'wrong_answer_flashcards_created') {
            $typesToCheck[] = 'wrong_answer_flashcards';
        }

        // Fetch active achievements matching these types
        $achievements = Achievement::whereIn('type', $typesToCheck)
            ->where('is_active', true)
            ->get();

        // Cache statistics computed during this request to avoid executing duplicate queries
        $cachedValues = [];

        foreach ($achievements as $achievement) {
            $value = null;

            switch ($achievement->type) {
                case 'quiz_completed':
                    if (!isset($cachedValues['quiz_completed'])) {
                        $cachedValues['quiz_completed'] = Result::where('user_id', $user->id)->count();
                    }
                    $value = $cachedValues['quiz_completed'];
                    break;

                case 'perfect_score':
                    if (!isset($cachedValues['perfect_score'])) {
                        $cachedValues['perfect_score'] = Result::where('user_id', $user->id)
                            ->where('percentage', 100)
                            ->count();
                    }
                    $value = $cachedValues['perfect_score'];
                    break;

                case 'streak':
                    $value = (int) $user->current_streak;
                    break;

                case 'flashcard_review':
                    if (!isset($cachedValues['flashcard_review'])) {
                        $cachedValues['flashcard_review'] = (int) FlashcardProgress::where('user_id', $user->id)
                            ->sum('review_count');
                    }
                    $value = $cachedValues['flashcard_review'];
                    break;

                case 'wrong_answer_flashcards':
                    if (!isset($cachedValues['wrong_answer_flashcards'])) {
                        $cachedValues['wrong_answer_flashcards'] = FlashcardSet::where('user_id', $user->id)
                            ->where('source_type', 'quiz_wrong_answers')
                            ->count();
                    }
                    $value = $cachedValues['wrong_answer_flashcards'];
                    break;
            }

            if ($value !== null && $value >= (int) $achievement->target_value) {
                $unlocked[] = $this->unlock($user, $achievement->code);
            }
        }

        // Return list of unique achievements unlocked in this request
        return collect($unlocked)->filter()->values()->all();
    }
}
