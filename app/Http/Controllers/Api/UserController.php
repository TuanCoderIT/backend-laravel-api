<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UserRequest;
use App\Models\User;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function index()
    {
        return response()->json(User::all());
    }

    public function store(UserRequest $request)
    {
        $data = $request->validated();

        if ($request->hasFile('avatar')) {
            $file = $request->file('avatar');
            $filename = time() . '-' . $file->getClientOriginalName();
            $path = $file->storeAs('uploads/avatars', $filename, 'public'); // lưu trong storage/app/public/uploads/avatars
            $data['avatar'] = asset('storage/uploads/avatars/' . $filename); // Tạo URL đầy đủ luôn
        }

        $user = User::create($data);
        return response()->json($user, 201);
    }

    public function show($id)
    {
        $user = User::findOrFail($id);
        return response()->json($user);
    }

    public function update(Request $request, $id)
    {
        $user = User::findOrFail($id);

        $validatedData = $request->validate([
            'name' => 'required|string|max:255',
            'role' => 'required|string|in:user,admin,editor',
            'status' => 'required|in:active,inactive',
            'phone_number' => 'nullable|string|max:20',
            'avatar' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
        ]);

        if ($request->hasFile('avatar')) {
            if ($user->avatar && Storage::exists($user->avatar)) {
                Storage::delete($user->avatar);
            }
            $file = $request->file('avatar');
            $filename = time() . '-' . $file->getClientOriginalName();
            $path = $file->storeAs('uploads/avatars', $filename, 'public'); // lưu trong storage/app/public/uploads/avatars
            $validatedData['avatar'] = asset('storage/uploads/avatars/' . $filename); // Tạo URL đầy đủ
        }
        $user->update($validatedData);

        return response()->json($user);
    }

    public function destroy($id)
    {
        $user = User::findOrFail($id);

        if ($user->avatar && Storage::exists($user->avatar)) {
            Storage::delete($user->avatar);
        }
        $user->delete();
        return response()->json(['message' => 'User deleted successfully']);
    }
}
