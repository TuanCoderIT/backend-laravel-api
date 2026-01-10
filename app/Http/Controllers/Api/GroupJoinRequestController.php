<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\GroupJoinRequest;
use App\Models\GroupMember;
use App\Models\Group;
use App\Models\ChatThread;
use App\Models\ChatParticipant;
use Illuminate\Support\Facades\Auth;

class GroupJoinRequestController extends Controller
{
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

        GroupMember::firstOrCreate([
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

        return response()->json(['message' => 'Request approved']);
    }

    public function reject($requestId)
    {
        $req = GroupJoinRequest::findOrFail($requestId);

        $req->update(['status' => 'rejected']);

        return response()->json(['message' => 'Request rejected']);
    }
}
