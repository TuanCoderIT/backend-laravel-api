<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Group;
use App\Models\GroupMember;
use Illuminate\Http\Request;

class AdminGroupMemberController extends Controller
{
    /**
     * Danh sách thành viên group
     */
    public function index($groupId)
    {
        $group = Group::with('members.user')->findOrFail($groupId);
        return response()->json($group->members);
    }

    /**
     * Admin đá user khỏi group
     */
    public function remove($groupId, $userId)
    {
        $deleted = GroupMember::where('group_id', $groupId)
            ->where('user_id', $userId)
            ->delete();

        return response()->json([
            'message' => $deleted
                ? 'User removed from group'
                : 'User not found'
        ]);
    }

    /**
     * Admin đổi role thành viên
     */
    public function changeRole(Request $request, $groupId, $userId)
    {
        $request->validate([
            'role' => 'required|in:member,moderator,admin'
        ]);

        $member = GroupMember::where('group_id', $groupId)
            ->where('user_id', $userId)
            ->firstOrFail();

        $member->role = $request->role;
        $member->save();

        return response()->json(['message' => 'Role updated']);
    }
}
