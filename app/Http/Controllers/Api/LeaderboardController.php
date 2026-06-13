<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;

class LeaderboardController extends Controller
{
    public function index(Request $request)
    {
        $currentUser = $request->user();

        $users = User::query()
            ->select('id', 'name', 'avatar', 'xp')
            ->orderByDesc('xp')
            ->orderBy('id')
            ->limit(100)
            ->get();

        $leaderboard = $users->map(function ($user, $index) {
            return [
                'rank' => $index + 1,
                'id' => $user->id,
                'name' => $user->name,
                'avatar' => $user->avatar,
                'xp' => $user->xp,
                'isCurrentUser' => false,
            ];
        });

        $currentUserRank = User::query()
            ->where('xp', '>', $currentUser->xp)
            ->count() + 1;

        return response()->json([
            'data' => $leaderboard->map(function ($item) use ($currentUser) {
                $item['isCurrentUser'] = $item['id'] === $currentUser->id;
                return $item;
            }),
            'me' => [
                'rank' => $currentUserRank,
                'id' => $currentUser->id,
                'name' => $currentUser->name,
                'avatar' => $currentUser->avatar,
                'xp' => $currentUser->xp,
            ],
        ]);
    }
}
