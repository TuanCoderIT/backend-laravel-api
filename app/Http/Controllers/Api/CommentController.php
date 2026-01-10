<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Post;
use App\Models\PostComment;
use App\Events\CommentCreated;

class CommentController extends Controller
{
    /**
     * Lấy danh sách comment của 1 bài viết (kèm reply)
     */
    public function index(Post $post)
    {
        $comments = PostComment::where('post_id', $post->id)
            ->whereNull('parent_id')
            ->with([
                'user:id,name',
                'replies.user:id,name',
                'replies.reactionSummary',
                'reactionSummary',
            ])
            ->orderByDesc('id')
            ->get();

        return response()->json($comments);
    }

    /**
     * Tạo comment (hoặc reply)
     */
    public function store(Request $request, Post $post)
    {
        $request->validate([
            'content' => 'required|string|max:2000',
            'parent_id' => 'nullable|exists:post_comments,id',
        ]);

        $comment = PostComment::create([
            'user_id' => $request->user()->id,
            'post_id' => $post->id,
            'content' => $request->input('content', null),
            'parent_id' => $request->parent_id,
        ]);

        // Broadcast realtime event
        broadcast(new CommentCreated($comment));

        return response()->json([
            'message' => 'Comment created successfully',
            'data' => $comment->load('user:id,name')
        ]);
    }

    /**
     * Xoá comment
     */
    public function destroy(PostComment $comment, Request $request)
    {
        if ($comment->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Bạn không có quyền xoá comment này'], 403);
        }

        $comment->delete(); // Laravel sẽ xóa luôn reply nếu bạn set cascade trong DB

        return response()->json(['message' => 'Comment deleted']);
    }
}
