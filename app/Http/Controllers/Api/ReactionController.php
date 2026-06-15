<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Post;
use App\Models\PostComment;
use App\Models\Reaction;
use App\Events\PostReacted;
use App\Events\CommentReacted;
use Illuminate\Support\Facades\Log;

class ReactionController extends Controller
{
    /**
     * React cho bài viết và bình luận
     */
    public function react(Request $request)
    {
        $request->validate([
            'target_type' => 'required|string|in:post,comment',
            'target_id' => 'required|integer',
            'reaction_type' => 'required|string|in:like,love,haha,wow,sad,angry'
        ]);

        $userId = $request->user()->id;

        // Map target_type -> Model
        $model = $request->target_type === 'post' ? Post::class : PostComment::class;
        $target = $model::findOrFail($request->target_id);

        // Xoá reaction cũ (nếu có)
        Reaction::where('reactionable_type', $model)
            ->where('reactionable_id', $request->target_id)
            ->where('user_id', $userId)
            ->delete();

        // Tạo phản ứng mới
        $reaction = Reaction::create([
            'reactionable_type' => $model,
            'reactionable_id'   => $request->target_id,
            'user_id'           => $userId,
            'reaction_type'     => $request->reaction_type,
        ]);

        // Broadcast realtime event
        $this->broadcastReaction($request->target_type, $target, $reaction, 'added');

        return response()->json([
            'message' => 'Reaction saved',
            'data' => $reaction
        ]);
    }

    /**
     * Xoá reaction
     */
    public function remove(Request $request)
    {
        $request->validate([
            'target_type' => 'required|string|in:post,comment',
            'target_id' => 'required|integer'
        ]);

        $model = $request->target_type === 'post' ? Post::class : PostComment::class;
        $target = $model::findOrFail($request->target_id);

        Reaction::where('reactionable_type', $model)
            ->where('reactionable_id', $request->target_id)
            ->where('user_id', $request->user()->id)
            ->delete();

        // Broadcast realtime event
        $this->broadcastReaction($request->target_type, $target, null, 'removed');
        return response()->json([
            'message' => 'Reaction removed'
        ]);
    }

    /**
     * Lấy danh sách reactions của một target (post hoặc comment)
     */
    public function getReactions(Request $request)
    {
        $request->validate([
            'target_type' => 'required|string|in:post,comment',
            'target_id' => 'required|integer'
        ]);

        $model = $request->target_type === 'post' ? Post::class : PostComment::class;

        $reactions = Reaction::where('reactionable_type', $model)
            ->where('reactionable_id', $request->target_id)
            ->with('user:id,name')
            ->get()
            ->groupBy('reaction_type')
            ->map(function ($reactions, $type) {
                return [
                    'type' => $type,
                    'count' => $reactions->count(),
                    'users' => $reactions->map(function ($reaction) {
                        return [
                            'id' => $reaction->user->id,
                            'name' => $reaction->user->name
                        ];
                    })
                ];
            })
            ->values();

        return response()->json($reactions);
    }

    private function broadcastReaction(string $targetType, $target, $reaction, string $action): void
    {
        try {
            $event = $targetType === 'post'
                ? new PostReacted($target, $reaction, $action)
                : new CommentReacted($target, $reaction, $action);

            broadcast($event);
        } catch (\Throwable $exception) {
            Log::warning('Reaction saved but broadcast failed', [
                'target_type' => $targetType,
                'target_id' => $target->id,
                'error' => $exception->getMessage(),
            ]);
        }
    }
}
