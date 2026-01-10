<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use App\Models\Group;
use App\Models\GroupMember;
use App\Models\GroupJoinRequest;
use App\Models\ChatThread;
use App\Models\ChatParticipant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Traits\UploadsImage;
use Illuminate\Support\Str;

class GroupController extends Controller
{
    use UploadsImage;
    public function index(Request $request)
    {
        $query = Group::withCount(['members', 'posts']);

        if ($request->has('search')) {
            $query->where('name', 'like', '%' . $request->search . '%')
                ->orWhere('description', 'like', '%' . $request->search . '%');
        }

        if ($request->has('visibility')) {
            $query->where('visibility', $request->visibility);
        }

        $sortBy = $request->get('sort_by', 'latest');
        switch ($sortBy) {
            case 'members':
                $query->orderBy('members_count', 'desc');
                break;
            case 'oldest':
                $query->oldest();
                break;
            default:
                $query->latest();
        }

        return response()->json($query->paginate($request->get('per_page', 20)));
    }
    public function store(Request $request)
    {
        $imagePath = null;

        $data = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'cover_image' => 'nullable|image|max:2048',
            'visibility' => 'required|in:public,private'
        ]);

        try {
            $imagePath = $this->handleImageUpload($request, 'groups', 'cover_image');
            $data['cover_image'] = $imagePath;

            $data['owner_id'] = Auth::id();
            $data['slug'] = Str::slug($data['name']) . '-' . uniqid();

            $group = DB::transaction(function () use ($data) {

                $group = Group::create($data);

                GroupMember::create([
                    'group_id' => $group->id,
                    'user_id' => Auth::id(),
                    'role' => 'admin'
                ]);

                $thread = ChatThread::create([
                    'type' => 'group',
                    'name' => $group->name,
                    'group_id' => $group->id,
                    'owner_id' => $group->owner_id,
                ]);

                ChatParticipant::create([
                    'thread_id' => $thread->id,
                    'user_id' => Auth::id(),
                ]);

                return $group;
            });

            return response()->json([
                'message' => 'Group created',
                'data' => $group->loadCount(['members', 'posts'])
            ], 201);

        } catch (\Exception $e) {
            $this->deleteImageFile($imagePath);

            \Log::error("Group creation failed: " . $e->getMessage());
            return response()->json(['message' => 'Group creation failed.'], 500);
        }
    }

    public function show($slug)
    {
        $group = Group::where('slug', $slug)
            ->with(['members.user:id,name', 'owner:id,name'])
            ->withCount(['members', 'posts'])
            ->firstOrFail();

        if (
            $group->visibility === 'private' &&
            !GroupMember::where('group_id', $group->id)
                ->where('user_id', Auth::id())
                ->exists()
        ) {
            return response()->json(['message' => 'This group is private'], 403);
        }

        return response()->json($group);
    }

    public function update(Request $request, Group $group)
    {

        if ($group->owner_id !== Auth::id()) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $validatedData = $request->validate([
            'description' => 'nullable|string',
            'cover_image' => 'nullable|image|max:2048',
            'visibility' => 'in:public,private'
        ]);

        $newImagePath = null;

        try {

            if ($request->hasFile('cover_image')) {
                $newImagePath = $this->handleImageUpload($request, 'groups', 'cover_image');

                $this->deleteImageFile($group->cover_image);

                $validatedData['cover_image'] = $newImagePath;

            } else if ($request->input('cover_image') === null && $group->cover_image) {
                $this->deleteImageFile($group->cover_image);
                $validatedData['cover_image'] = null;
            }

            $group->update($validatedData);

            return response()->json(['message' => 'Group updated']);

        } catch (\Exception $e) {
            if ($newImagePath) {
                $this->deleteImageFile($newImagePath);
            }
            \Log::error("Group update failed: " . $e->getMessage());
            return response()->json(['message' => 'Group update failed.'], 500);
        }
    }

    public function destroy(Group $group)
    {
        // Kiểm tra quyền hạn (nên dùng Policy)
        if ($group->owner_id !== Auth::id()) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $oldImagePath = $group->cover_image;

        try {
            DB::transaction(function () use ($group) {
                $group->delete();
            });

            $this->deleteImageFile($oldImagePath);

            return response()->json(['message' => 'Group deleted']);

        } catch (\Exception $e) {
            \Log::error("Group deletion failed: " . $e->getMessage());
            return response()->json(['message' => 'Group deletion failed.'], 500);
        }
    }

    /**
     * Lấy danh sách groups của user hiện tại
     */
    public function myGroups()
    {
        $groupIds = GroupMember::where('user_id', Auth::id())
            ->pluck('group_id');

        $groups = Group::whereIn('id', $groupIds)
            ->withCount(['members', 'posts'])
            ->with(['owner:id,name'])
            ->latest()
            ->get();

        return response()->json($groups);
    }

    /**
     * Kiểm tra membership status của user trong group
     */
    public function checkMembership($groupId)
    {
        $group = Group::findOrFail($groupId);

        $member = GroupMember::where('group_id', $groupId)
            ->where('user_id', Auth::id())
            ->first();

        $joinRequest = GroupJoinRequest::where('group_id', $groupId)
            ->where('user_id', Auth::id())
            ->where('status', 'pending')
            ->first();

        return response()->json([
            'is_member' => $member !== null,
            'role' => $member ? $member->role : null,
            'is_owner' => $group->owner_id === Auth::id(),
            'has_pending_request' => $joinRequest !== null,
        ]);
    }

    /**
     * Lấy danh sách members của group
     */
    public function members($groupId, Request $request)
    {
        $group = Group::findOrFail($groupId);

        // Private group: chỉ members xem được
        if ($group->visibility === 'private') {
            $isMember = GroupMember::where('group_id', $groupId)
                ->where('user_id', Auth::id())
                ->exists();

            if (!$isMember) {
                return response()->json(['message' => 'This group is private'], 403);
            }
        }

        $query = GroupMember::where('group_id', $groupId)
            ->with('user:id,name');

        // Filter by role
        if ($request->has('role')) {
            $query->where('role', $request->role);
        }

        // Search by user name
        if ($request->has('search')) {
            $query->whereHas('user', function ($q) use ($request) {
                $q->where('name', 'like', '%' . $request->search . '%');
            });
        }

        return response()->json($query->get());
    }
}
