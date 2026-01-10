<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ChatMessage;
use App\Models\ChatParticipant;
use App\Models\ChatThread;
use App\Models\Reaction;
use App\Events\NewChatMessage;
use App\Events\UserTypingInThread;
use App\Events\ThreadRead;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
class ChatController extends Controller
{
    public function myThreads()
    {
        $threads = ChatThread::whereHas('participants', function ($q) {
            $q->where('user_id', Auth::id());
        })
            ->with(['participants.user:id,name'])
            ->latest()
            ->get();

        return response()->json($threads);
    }

    public function directThread(Request $request)
    {
        $request->validate(['user_id' => 'required|integer']);

        $me = Auth::id();
        $other = $request->user_id;

        $thread = ChatThread::where('type', 'direct')
            ->whereHas('participants', fn($q) => $q->where('user_id', $me))
            ->whereHas('participants', fn($q) => $q->where('user_id', $other))
            ->first();

        if (!$thread) {
            $thread = ChatThread::create(['type' => 'direct']);

            ChatParticipant::insert([
                ['thread_id' => $thread->id, 'user_id' => $me, 'created_at' => now(), 'updated_at' => now()],
                ['thread_id' => $thread->id, 'user_id' => $other, 'created_at' => now(), 'updated_at' => now()],
            ]);
        }

        return response()->json($thread);
    }

    public function messages(Request $request, $threadId)
    {
        $limit = $request->get('limit', 30);

        $messages = ChatMessage::where('thread_id', $threadId)
            ->with('user:id,name')
            ->orderBy('created_at', 'asc')
            ->paginate($limit);

        return response()->json($messages);
    }

    public function send(Request $request, $threadId)
    {
        $request->validate([
            'content' => 'nullable|string',
            'attachments' => 'nullable|array',
        ]);

        $msg = ChatMessage::create([
            'thread_id' => $threadId,
            'user_id' => Auth::id(),
            'content' => $request->input('content', null),
            'attachments' => $request->input('attachments', null),
        ]);

        // Cập nhật last_read_at cho người gửi
        ChatParticipant::where('thread_id', $threadId)
            ->where('user_id', Auth::id())
            ->update(['last_read_at' => now()]);

        // Broadcast tin nhắn mới
        // broadcast(new NewChatMessage($msg))->toOthers();
        broadcast(new NewChatMessage($msg));
        // Log::info('New chat message broadcasted', ['message' => $msg]);

        return response()->json($msg);
    }

    public function markRead($threadId)
    {
        ChatParticipant::where('thread_id', $threadId)
            ->where('user_id', Auth::id())
            ->update(['last_read_at' => now()]);

        broadcast(new ThreadRead(
            $threadId,
            Auth::id(),
            Auth::user()->name ?? 'Unknown'
        ))->toOthers();

        return response()->json(['message' => 'Marked as read']);
    }

    public function typing($threadId)
    {
        broadcast(new UserTypingInThread(
            $threadId,
            Auth::id(),
            Auth::user()->name ?? 'Unknown'
        ))->toOthers();

        return response()->json(['typing' => true]);
    }

    public function reactMessage(Request $request, $messageId)
    {
        $request->validate([
            'reaction_type' => 'required|string|max:10'
        ]);

        $message = ChatMessage::findOrFail($messageId);

        $message->reactions()->updateOrCreate(
            ['user_id' => Auth::id()],
            ['reaction_type' => $request->reaction_type]
        );

        return response()->json(['message' => 'Reacted']);
    }

    public function removeReaction($messageId)
    {
        $message = ChatMessage::findOrFail($messageId);

        $message->reactions()->where('user_id', Auth::id())->delete();

        return response()->json(['message' => 'Reaction removed']);
    }

    /**
     * Lấy hoặc tạo group chat thread
     */
    public function groupThread($groupId)
    {
        $group = \App\Models\Group::findOrFail($groupId);

        // Kiểm tra user có phải member không
        $isMember = \App\Models\GroupMember::where('group_id', $groupId)
            ->where('user_id', Auth::id())
            ->exists();

        if (!$isMember) {
            return response()->json(['message' => 'You must be a member to access group chat'], 403);
        }

        // Tìm hoặc tạo thread
        $thread = ChatThread::where('type', 'group')
            ->where('group_id', $groupId)
            ->first();

        if (!$thread) {
            // Tạo thread mới
            $thread = ChatThread::create([
                'type' => 'group',
                'name' => $group->name,
                'group_id' => $groupId,
                'owner_id' => $group->owner_id,
            ]);

            // Thêm tất cả members vào participants
            $members = \App\Models\GroupMember::where('group_id', $groupId)->pluck('user_id');
            foreach ($members as $userId) {
                ChatParticipant::firstOrCreate([
                    'thread_id' => $thread->id,
                    'user_id' => $userId,
                ]);
            }
        } else {
            // Đảm bảo user hiện tại có trong participants
            ChatParticipant::firstOrCreate([
                'thread_id' => $thread->id,
                'user_id' => Auth::id(),
            ]);
        }

        $thread->load(['participants.user:id,name']);

        return response()->json($thread->load('group:id,name,slug'));
    }
}
