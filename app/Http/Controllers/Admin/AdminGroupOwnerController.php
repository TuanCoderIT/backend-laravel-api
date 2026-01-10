<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Group;
use Illuminate\Http\Request;

class AdminGroupOwnerController extends Controller
{
    /**
     * Đổi owner group
     */
    public function change(Request $request, $groupId)
    {
        $request->validate([
            'new_owner_id' => 'required|exists:users,id'
        ]);

        $group = Group::findOrFail($groupId);
        $group->owner_id = $request->new_owner_id;
        $group->save();

        return response()->json(['message' => 'Owner changed']);
    }
}
