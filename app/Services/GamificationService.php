<?php

namespace App\Services;

use App\Models\User;
use App\Models\XpLog;
use Carbon\Carbon;

class GamificationService
{
    public function reward(
        User $user,
        int $xp,
        string $sourceType,
        ?int $sourceId = null,
        ?string $description = null
    ): void {
        $this->addXp($user, $xp, $sourceType, $sourceId, $description);

        $this->updateStreak($user);

        $user->save();
    }

    public function addXp(
        User $user,
        int $xp,
        string $sourceType,
        ?int $sourceId = null,
        ?string $description = null
    ): void {
        $user->increment('xp', $xp);

        XpLog::create([
            'user_id' => $user->id,
            'amount' => $xp,
            'source_type' => $sourceType,
            'source_id' => $sourceId,
            'description' => $description,
        ]);
    }

    private function updateStreak(User $user): void
    {
        $today = Carbon::today();
        $lastActivityDate = $user->last_activity_at
            ? $user->last_activity_at->copy()->startOfDay()
            : null;

        if ($lastActivityDate && $lastActivityDate->isSameDay($today)) {
            return;
        }

        if (!$lastActivityDate) {
            $user->current_streak = 1;
        } else {
            $daysSinceLastActivity = (int) $lastActivityDate->diffInDays($today);

            if ($daysSinceLastActivity === 1) {
                $user->current_streak = (int) $user->current_streak + 1;
            } elseif ($daysSinceLastActivity === 2 && (int) $user->streak_freezes > 0) {
                $user->streak_freezes = (int) $user->streak_freezes - 1;
            } else {
                $user->current_streak = 1;
            }
        }

        if ((int) $user->current_streak > (int) $user->longest_streak) {
            $user->longest_streak = $user->current_streak;
        }

        $user->last_activity_at = Carbon::now();
    }
}
