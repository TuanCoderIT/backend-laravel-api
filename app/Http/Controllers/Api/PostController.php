<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Post;
use App\Models\Group;
use App\Events\PostCreated;
use App\Events\PostShared;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class PostController extends Controller
{
    /**
     * FEED CHUNG (global feed)
     */
    public function index(Request $request)
    {
        $posts = Post::with([
            'user:id,name,avatar',
            'group:id,name',
            'reactions.user:id,name',
            'target'
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
                'user:id,name,avatar',
                'reactions.user:id,name',
                'target'
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
            'attachments' => 'nullable|array|max:4',
            'attachments.*' => 'nullable|string',
            'group_id' => 'nullable|exists:groups,id',
            'visibility' => 'nullable|in:public,private,group_only',
            'target_type' => 'nullable|string',
            'target_id' => 'nullable|integer'
        ]);

        $attachments = $this->prepareAttachments($request->input('attachments', []));

        $post = Post::create([
            'user_id' => $request->user()->id,
            'content' => $request->input('content', null),
            'attachments' => $attachments,
            'group_id' => $request->group_id,
            'visibility' => $request->visibility ?? 'public',
            'target_type' => $request->target_type,
            'target_id' => $request->target_id,
        ]);

        broadcast(new PostCreated($post));

        return response()->json([
            'message' => 'Post created successfully',
            'data' => $post->load('user:id,name,avatar')
        ], 201);
    }

    /**
     * XEM CHI TIẾT POST
     */
    public function show(Post $post)
    {
        $post->load([
            'user:id,name,avatar',
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
            'attachments' => 'nullable|array|max:4',
            'attachments.*' => 'nullable|string',
            'visibility' => 'nullable|in:public,private,group_only'
        ]);

        $attachments = $request->has('attachments')
            ? $this->prepareAttachments($request->input('attachments', []))
            : $post->attachments;

        $post->update([
            'content' => $request->has('content')
                ? $request->input('content')
                : $post->content,
            'attachments' => $attachments,
            'visibility' => $request->visibility ?? $post->visibility,
        ]);

        return response()->json([
            'message' => 'Post updated successfully',
            'data' => $post->fresh()->load('user:id,name,avatar')
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

        $this->deleteStoredAttachments($post->attachments ?? []);

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

        $sharedPost = Post::create([
            'user_id' => $request->user()->id,
            'content' => $request->input('content', null),
            'group_id' => $request->group_id,
            'visibility' => $request->visibility ?? 'public',
            'target_type' => 'App\\Models\\Post',
            'target_id' => $originalPost->id,
        ]);

        broadcast(new PostShared($originalPost, $sharedPost));

        return response()->json([
            'message' => 'Post shared successfully',
            'data' => $sharedPost->load(['user:id,name,avatar', 'target'])
        ], 201);
    }

    /**
     * Convert attachments base64 thành file storage.
     */
    private function prepareAttachments(?array $attachments): array
    {
        if (empty($attachments)) {
            return [];
        }

        return collect($attachments)
            ->filter(fn($item) => is_string($item) && trim($item) !== '')
            ->map(function ($attachment) {
                if ($this->isBase64Image($attachment)) {
                    return $this->storeBase64Image($attachment);
                }

                return $attachment;
            })
            ->filter()
            ->values()
            ->all();
    }

    private function isBase64Image(string $value): bool
    {
        return preg_match('/^data:image\/(\w+);base64,/', $value) === 1;
    }

    private function storeBase64Image(string $base64Image): ?string
    {
        try {
            [$meta, $content] = explode(',', $base64Image, 2);

            preg_match('/^data:image\/(\w+);base64$/', $meta, $matches);

            $extension = strtolower($matches[1] ?? 'jpg');

            if ($extension === 'jpeg') {
                $extension = 'jpg';
            }

            $allowedExtensions = ['jpg', 'png', 'webp', 'gif'];

            if (!in_array($extension, $allowedExtensions, true)) {
                $extension = 'jpg';
            }

            $binary = base64_decode($content, true);

            if ($binary === false) {
                return null;
            }

            $filename = 'uploads/posts/' . Str::uuid() . '.' . $extension;

            Storage::disk('public')->put($filename, $binary);

            return '/storage/' . $filename;
        } catch (\Throwable $e) {
            report($e);
            return null;
        }
    }

    private function deleteStoredAttachments(array $attachments): void
    {
        foreach ($attachments as $attachment) {
            if (!is_string($attachment)) {
                continue;
            }

            if (!str_starts_with($attachment, '/storage/uploads/posts/')) {
                continue;
            }

            $path = str_replace('/storage/', '', $attachment);

            if (Storage::disk('public')->exists($path)) {
                Storage::disk('public')->delete($path);
            }
        }
    }
}
