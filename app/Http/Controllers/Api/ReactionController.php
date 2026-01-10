<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Post;
use App\Models\PostComment;
use App\Models\Reaction;
use App\Events\PostReacted;
use App\Events\CommentReacted;

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
            'reaction_type' => 'required|string|in:like,love,haha,sad,angry'
        ]);

        $userId = $request->user()->id;

        // Map target_type -> Model
        $model = $request->target_type === 'post' ? Post::class : PostComment::class;

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
        if ($request->target_type === 'post') {
            $post = Post::find($request->target_id);
            broadcast(new PostReacted($post, $reaction, 'added'));
        } else {
            $comment = PostComment::find($request->target_id);
            broadcast(new CommentReacted($comment, $reaction, 'added'));
        }

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

        Reaction::where('reactionable_type', $model)
            ->where('reactionable_id', $request->target_id)
            ->where('user_id', $request->user()->id)
            ->delete();

        // Broadcast realtime event
        if ($request->target_type === 'post') {
            $post = Post::find($request->target_id);
            broadcast(new PostReacted($post, null, 'removed'));
        } else {
            $comment = PostComment::find($request->target_id);
            broadcast(new CommentReacted($comment, null, 'removed'));
        }
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
}