<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Achievement;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AchievementController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Achievement::query()->latest();

        if ($request->filled('search')) {
            $search = $request->search;

            $query->where(function ($q) use ($search) {
                $q->where('code', 'like', "%{$search}%")
                    ->orWhere('name', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        if ($request->filled('rarity')) {
            $query->where('rarity', $request->rarity);
        }

        if ($request->has('is_active')) {
            $query->where('is_active', filter_var($request->is_active, FILTER_VALIDATE_BOOLEAN));
        }

        return response()->json([
            'success' => true,
            'message' => 'Lấy danh sách danh hiệu thành công.',
            'data' => $query->paginate($request->integer('per_page', 10)),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'code' => ['required', 'string', 'max:100', 'unique:achievements,code'],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'icon' => ['nullable', 'string', 'max:100'],
            'type' => ['required', 'string', 'max:50'],
            'rarity' => ['required', Rule::in(['common', 'rare', 'epic', 'legendary'])],
            'target_value' => ['required', 'integer', 'min:1'],
            'xp_reward' => ['nullable', 'integer', 'min:0'],
            'token_reward' => ['nullable', 'integer', 'min:0'],
            'reward_title' => ['nullable', 'string', 'max:255'],
            'reward_trophy' => ['nullable', 'string', 'max:255'],
            'conditions' => ['nullable', 'array'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $achievement = Achievement::create([
            ...$validated,
            'xp_reward' => $validated['xp_reward'] ?? 0,
            'token_reward' => $validated['token_reward'] ?? 0,
            'is_active' => $validated['is_active'] ?? true,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Tạo danh hiệu thành công.',
            'data' => $achievement,
        ], 201);
    }

    public function show(Achievement $achievement): JsonResponse
    {
        $achievement->loadCount('userAchievements');

        return response()->json([
            'success' => true,
            'message' => 'Lấy chi tiết danh hiệu thành công.',
            'data' => $achievement,
        ]);
    }

    public function update(Request $request, Achievement $achievement): JsonResponse
    {
        $validated = $request->validate([
            'code' => [
                'sometimes',
                'required',
                'string',
                'max:100',
                Rule::unique('achievements', 'code')->ignore($achievement->id),
            ],
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'icon' => ['nullable', 'string', 'max:100'],
            'type' => ['sometimes', 'required', 'string', 'max:50'],
            'rarity' => ['sometimes', 'required', Rule::in(['common', 'rare', 'epic', 'legendary'])],
            'target_value' => ['sometimes', 'required', 'integer', 'min:1'],
            'xp_reward' => ['nullable', 'integer', 'min:0'],
            'token_reward' => ['nullable', 'integer', 'min:0'],
            'reward_title' => ['nullable', 'string', 'max:255'],
            'reward_trophy' => ['nullable', 'string', 'max:255'],
            'conditions' => ['nullable', 'array'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $achievement->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Cập nhật danh hiệu thành công.',
            'data' => $achievement->fresh()->loadCount('userAchievements'),
        ]);
    }

    public function destroy(Achievement $achievement): JsonResponse
    {
        if ($achievement->userAchievements()->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Không thể xóa danh hiệu đã có người dùng đạt được. Hãy tắt trạng thái hoạt động thay vì xóa.',
            ], 422);
        }

        $achievement->delete();

        return response()->json([
            'success' => true,
            'message' => 'Xóa danh hiệu thành công.',
        ]);
    }
}
