<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Post;
use App\Models\Group;
use App\Events\PostCreated;
use App\Events\PostShared;

class PostController extends Controller
{
    /**
     * FEED CHUNG (global feed)
     */
    public function index(Request $request)
    {
        $posts = Post::with([
            'user:id,name',
            'group:id,name',
            'reactions.user:id,name',
            'target' // For shared posts
        ])
            ->withCount(['comments', 'reactions'])
            ->latest()
            ->paginate(10);

        return response()->json($posts);
    }

    /**
     * FEED THEO GROUP
     */
    public function indexGroup(Group $group, Request $request)
    {
        $posts = Post::where('group_id', $group->id)
            ->with([
                'user:id,name',
                'reactions.user:id,name',
                'target' // For shared posts
            ])
            ->withCount(['comments', 'reactions'])
            ->latest()
            ->paginate(10);

        return response()->json($posts);
    }

    /**
     * TẠO POST
     */
    public function store(Request $request)
    {
        $request->validate([
            'content' => 'nullable|string',
            'attachments' => 'nullable|array',
            'group_id' => 'nullable|exists:groups,id',
            'visibility' => 'nullable|in:public,private,group_only',
            'target_type' => 'nullable|string',
            'target_id' => 'nullable|integer'
        ]);

        $post = Post::create([
            'user_id' => $request->user()->id,
            'content' => $request->input('content', null),
            'attachments' => $request->attachments,
            'group_id' => $request->group_id,
            'visibility' => $request->visibility ?? 'public',
            'target_type' => $request->target_type,
            'target_id' => $request->target_id,
        ]);

        // Broadcast realtime event
        broadcast(new PostCreated($post));

        return response()->json([
            'message' => 'Post created successfully',
            'data' => $post->load('user:id,name')
        ], 201);
    }

    /**
     * XEM CHI TIẾT POST
     */
    public function show(Post $post)
    {
        $post->load([
            'user:id,name',
            'group:id,name',
            'reactions.user:id,name'
        ])->loadCount(['comments', 'reactions']);

        return response()->json($post);
    }

    /**
     * UPDATE POST
     */
    public function update(Request $request, Post $post)
    {
        if ($post->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Bạn không có quyền sửa bài viết này'], 403);
        }

        $request->validate([
            'content' => 'nullable|string',
            'attachments' => 'nullable|array',
            'visibility' => 'nullable|in:public,private,group_only'
        ]);

        $post->update([
            'content' => $request->input('content', null) ?? $post->content,
            'attachments' => $request->attachments ?? $post->attachments,
            'visibility' => $request->visibility ?? $post->visibility,
        ]);

        return response()->json([
            'message' => 'Post updated successfully',
            'data' => $post
        ]);
    }

    /**
     * XÓA POST
     */
    public function destroy(Post $post, Request $request)
    {
        if ($post->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Bạn không có quyền xoá bài viết này'], 403);
        }

        $post->delete();

        return response()->json(['message' => 'Post deleted']);
    }

    /**
     * SHARE POST
     */
    public function share(Request $request, Post $originalPost)
    {
        $request->validate([
            'content' => 'nullable|string|max:1000',
            'group_id' => 'nullable|exists:groups,id',
            'visibility' => 'nullable|in:public,private,group_only'
        ]);

        // Tạo bài viết share
        $sharedPost = Post::create([
            'user_id' => $request->user()->id,
            'content' => $request->input('content', null),
            'group_id' => $request->group_id,
            'visibility' => $request->visibility ?? 'public',
            'target_type' => 'App\\Models\\Post',
            'target_id' => $originalPost->id,
        ]);

        // Broadcast realtime event
        broadcast(new PostShared($originalPost, $sharedPost));

        return response()->json([
            'message' => 'Post shared successfully',
            'data' => $sharedPost->load(['user:id,name', 'target'])
        ], 201);
    }
}
