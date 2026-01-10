<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Group;
use Illuminate\Http\Request;

class AdminGroupController extends Controller
{
    /**
     * Danh sách group kèm tổng số thành viên
     */
    public function index()
    {
        $groups = Group::withCount('members')
            ->latest()
            ->paginate(30);

        return response()->json($groups);
    }

    /**
     * Chi tiết 1 group
     */
    public function show($id)
    {
        $group = Group::with([
                'owner',
                'members.user',
                'posts' => fn ($q) => $q->latest()->limit(20)
            ])
            ->findOrFail($id);

        return response()->json($group);
    }

    /**
     * Admin cập nhật thông tin group
     */
    public function update(Request $request, $id)
    {
        $group = Group::findOrFail($id);

        $group->update($request->only([
            'name', 'description', 'visibility', 'avatar'
        ]));

        return response()->json([
            'message' => 'Group updated',
            'data' => $group
        ]);
    }

    /**
     * Khóa group – không cho hoạt động nữa
     */
    public function lock($id)
    {
        $group = Group::findOrFail($id);
        $group->is_locked = true;
        $group->save();

        return response()->json(['message' => 'Group locked']);
    }

    /**
     * Xóa group (toàn quyền admin)
     */
    public function destroy($id)
    {
        $group = Group::findOrFail($id);
        $group->delete(); // soft delete nếu bạn dùng SoftDeletes

        return response()->json(['message' => 'Group deleted by admin']);
    }
}
