<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Requests\Public\UpdateProfileRequest;
use Illuminate\Support\Facades\Storage;

class ProfileController extends Controller
{
    public function show(Request $request)
    {
        return response()->json($request->user());
    }

    public function update(UpdateProfileRequest $request)
    {
        $user = $request->user();

        if ($request->hasFile('avatar')) {
            if ($user->avatar && Storage::exists($user->avatar)) {
                Storage::delete($user->avatar);
            }
            $file = $request->file('avatar');
            $filename = time() . '-' . $file->getClientOriginalName();
            $path = $file->storeAs('uploads/avatars', $filename, 'public'); // lưu trong storage/app/public/uploads/avatars
            $request['avatar'] = asset('storage/uploads/avatars/' . $filename); // Tạo URL đầy đủ
        }
        $user->update($request->validated());
        return response()->json($user);
    }
}
