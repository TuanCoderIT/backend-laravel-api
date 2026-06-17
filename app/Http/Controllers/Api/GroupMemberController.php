<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Group;
use App\Models\GroupMember;
use App\Models\GroupJoinRequest;
use App\Models\ChatThread;
use App\Models\ChatParticipant;
use App\Services\NotificationService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class GroupMemberController extends Controller
{
    public function __construct(
        private NotificationService $notificationService
    ) {
    }

    // Join group
    public function join($groupId)
    {
        $group = Group::findOrFail($groupId);

        // private → send join request
        if ($group->visibility === 'private') {

            GroupJoinRequest::firstOrCreate([
                'group_id' => $groupId,
                'user_id' => Auth::id()
            ]);

            return response()->json(['message' => 'Join request sent'], 202);
        }

        // public group → join trực tiếp
        $member = GroupMember::firstOrCreate([
            'group_id' => $groupId,
            'user_id' => Auth::id(),
        ]);

        $group->update(['members_count' => $group->members()->count()]);

        // Auto-join group chat thread
        $thread = ChatThread::where('type', 'group')
            ->where('group_id', $groupId)
            ->first();

        if ($thread) {
            ChatParticipant::firstOrCreate([
                'thread_id' => $thread->id,
                'user_id' => Auth::id(),
            ]);
        }

        if ($member->wasRecentlyCreated) {
            try {
                $this->notificationService->joinedGroup(Auth::id(), [
                    'group_id' => $group->id,
                    'group_name' => $group->name,
                    'group_slug' => $group->slug,
                ]);
            } catch (\Throwable $e) {
                Log::warning('Failed to create joined group notification', [
                    'user_id' => Auth::id(),
                    'group_id' => $group->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return response()->json(['message' => 'Joined group']);
    }

    // Leave group
    public function leave($groupId)
    {
        $group = Group::findOrFail($groupId);

        // Không cho owner leave group
        if ($group->owner_id === Auth::id()) {
            return response()->json(['message' => 'Owner cannot leave group. Please transfer ownership or delete group.'], 403);
        }

        GroupMember::where('group_id', $groupId)
            ->where('user_id', Auth::id())
            ->delete();

        $group->update(['members_count' => $group->members()->count()]);

        // Auto-leave group chat thread
        $thread = ChatThread::where('type', 'group')
            ->where('group_id', $groupId)
            ->first();

        if ($thread) {
            ChatParticipant::where('thread_id', $thread->id)
                ->where('user_id', Auth::id())
                ->delete();
        }

        return response()->json(['message' => 'Left group']);
    }

    // Kick member
    public function kick($groupId, $userId)
    {
        $group = Group::findOrFail($groupId);

        // chỉ owner hoặc admin mới kick
        $currentRole = GroupMember::where('group_id', $groupId)
            ->where('user_id', Auth::id())
            ->value('role');

        if (!in_array($currentRole, ['owner', 'admin'])) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        // Không cho kick owner
        if ($group->owner_id === $userId) {
            return response()->json(['message' => 'Cannot kick owner'], 403);
        }

        GroupMember::where('group_id', $groupId)
            ->where('user_id', $userId)
            ->delete();

        $group->update(['members_count' => $group->members()->count()]);

        // Auto-leave group chat thread
        $thread = ChatThread::where('type', 'group')
            ->where('group_id', $groupId)
            ->first();

        if ($thread) {
            ChatParticipant::where('thread_id', $thread->id)
                ->where('user_id', $userId)
                ->delete();
        }

        return response()->json(['message' => 'Member removed']);
    }

    // Promote
    public function promote($groupId, $userId)
    {
        GroupMember::where('group_id', $groupId)
            ->where('user_id', $userId)
            ->update(['role' => 'admin']);

        return response()->json(['message' => 'Promoted to admin']);
    }

    // Demote
    public function demote($groupId, $userId)
    {
        GroupMember::where('group_id', $groupId)
            ->where('user_id', $userId)
            ->update(['role' => 'member']);

        return response()->json(['message' => 'Demoted to member']);
    }
}
