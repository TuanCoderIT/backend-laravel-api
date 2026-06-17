<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\GroupJoinRequest;
use App\Models\GroupMember;
use App\Models\Group;
use App\Models\ChatThread;
use App\Models\ChatParticipant;
use App\Services\NotificationService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class GroupJoinRequestController extends Controller
{
    public function __construct(
        private NotificationService $notificationService
    ) {
    }

    public function list($groupId)
    {
        return GroupJoinRequest::where('group_id', $groupId)
            ->where('status', 'pending')
            ->with('user:id,name')
            ->get();
    }

    public function approve($requestId)
    {
        $req = GroupJoinRequest::findOrFail($requestId);

        $group = Group::findOrFail($req->group_id);

        $role = GroupMember::where('group_id', $group->id)
            ->where('user_id', Auth::id())
            ->value('role');

        if (!in_array($role, ['owner', 'admin'])) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $req->update(['status' => 'approved']);

        $member = GroupMember::firstOrCreate([
            'group_id' => $group->id,
            'user_id' => $req->user_id
        ]);

        $group->update(['members_count' => $group->members()->count()]);

        $thread = ChatThread::where('type', 'group')
            ->where('group_id', $group->id)
            ->first();

        if ($thread) {
            ChatParticipant::firstOrCreate([
                'thread_id' => $thread->id,
                'user_id' => $req->user_id,
            ]);
        }

        if ($member->wasRecentlyCreated) {
            try {
                $this->notificationService->joinedGroup($req->user_id, [
                    'group_id' => $group->id,
                    'group_name' => $group->name,
                    'group_slug' => $group->slug,
                ]);
            } catch (\Throwable $e) {
                Log::warning('Failed to create approved group join notification', [
                    'user_id' => $req->user_id,
                    'group_id' => $group->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return response()->json(['message' => 'Request approved']);
    }

    public function reject($requestId)
    {
        $req = GroupJoinRequest::findOrFail($requestId);

        $req->update(['status' => 'rejected']);

        return response()->json(['message' => 'Request rejected']);
    }
}
