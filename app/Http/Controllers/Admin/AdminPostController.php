<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Post;

class AdminPostController extends Controller
{
    /**
     * Danh sách mọi post (group + global)
     */
    public function index()
    {
        $posts = Post::with(['user', 'group'])
            ->latest()
            ->paginate(30);

        return response()->json($posts);
    }

    /**
     * Chi tiết 1 post
     */
    public function show($id)
    {
        $post = Post::with(['user', 'comments.user', 'reactions'])
            ->findOrFail($id);

        return response()->json($post);
    }

    /**
     * Admin xoá post
     */
    public function destroy($id)
    {
        $post = Post::findOrFail($id);
        $post->delete();

        return response()->json(['message' => 'Post deleted by admin']);
    }

    /**
     * Ẩn bài viết
     */
    public function hide($id)
    {
        $post = Post::findOrFail($id);
        $post->visibility = 'hidden';
        $post->save();

        return response()->json([
            'message' => 'Post hidden',
            'data' => $post
        ]);
    }
}

