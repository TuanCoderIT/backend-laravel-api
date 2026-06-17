<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\NotificationRequest;
use App\Services\NotificationService;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function __construct(
        private readonly NotificationService $notificationService
    ) {
    }

    public function index(Request $request)
    {
        $perPage = (int) $request->get('per_page', 15);
        $type = $request->get('type');

        $notifications = $request->user()
            ->notifications()
            ->when($type, function ($query) use ($type) {
                $query->where('type', $type);
            })
            ->latest()
            ->paginate($perPage);

        return response()->json([
            'message' => 'Lấy danh sách thông báo thành công',
            'data' => [
                'notifications' => $notifications->items(),
                'pagination' => [
                    'current_page' => $notifications->currentPage(),
                    'last_page' => $notifications->lastPage(),
                    'per_page' => $notifications->perPage(),
                    'total' => $notifications->total(),
                    'has_more' => $notifications->hasMorePages(),
                ],
            ],
        ]);
    }

    public function unreadCount(Request $request)
    {
        $count = $request->user()
            ->notifications()
            ->unread()
            ->count();

        return response()->json([
            'message' => 'Lấy số thông báo chưa đọc thành công',
            'data' => [
                'unread_count' => $count,
            ],
        ]);
    }

    public function unread(Request $request)
    {
        $limit = (int) $request->get('limit', 10);

        $notifications = $request->user()
            ->notifications()
            ->unread()
            ->latest()
            ->limit($limit)
            ->get();

        return response()->json([
            'message' => 'Lấy thông báo chưa đọc thành công',
            'data' => [
                'notifications' => $notifications,
                'count' => $notifications->count(),
            ],
        ]);
    }

    public function show(Request $request, $id)
    {
        $notification = $request->user()
            ->notifications()
            ->find($id);

        if (!$notification) {
            return response()->json([
                'message' => 'Không tìm thấy thông báo',
            ], 404);
        }

        if (!$notification->is_read) {
            $notification->markAsRead();
        }

        return response()->json([
            'message' => 'Lấy chi tiết thông báo thành công',
            'data' => [
                'notification' => $notification->fresh(),
            ],
        ]);
    }

    public function markAsRead(Request $request, $id)
    {
        $notification = $request->user()
            ->notifications()
            ->find($id);

        if (!$notification) {
            return response()->json([
                'message' => 'Không tìm thấy thông báo',
            ], 404);
        }

        if (!$notification->is_read) {
            $notification->markAsRead();
        }

        return response()->json([
            'message' => 'Đánh dấu đã đọc thành công',
            'data' => [
                'notification' => $notification->fresh(),
            ],
        ]);
    }

    public function markAllAsRead(Request $request)
    {
        $updatedCount = $request->user()
            ->notifications()
            ->unread()
            ->update([
                'read_at' => now(),
            ]);

        return response()->json([
            'message' => "Đã đánh dấu {$updatedCount} thông báo là đã đọc",
            'data' => [
                'updated_count' => $updatedCount,
            ],
        ]);
    }

    public function destroy(Request $request, $id)
    {
        $notification = $request->user()
            ->notifications()
            ->find($id);

        if (!$notification) {
            return response()->json([
                'message' => 'Không tìm thấy thông báo',
            ], 404);
        }

        $notification->delete();

        return response()->json([
            'message' => 'Xóa thông báo thành công',
        ]);
    }

    public function clearRead(Request $request)
    {
        $deletedCount = $request->user()
            ->notifications()
            ->read()
            ->delete();

        return response()->json([
            'message' => "Đã xóa {$deletedCount} thông báo đã đọc",
            'data' => [
                'deleted_count' => $deletedCount,
            ],
        ]);
    }

    public function stats(Request $request)
    {
        $user = $request->user();

        $stats = [
            'total' => $user->notifications()->count(),
            'unread' => $user->notifications()->unread()->count(),
            'read' => $user->notifications()->read()->count(),
            'today' => $user->notifications()->whereDate('created_at', today())->count(),
            'this_week' => $user->notifications()
                ->whereBetween('created_at', [
                    now()->startOfWeek(),
                    now()->endOfWeek(),
                ])
                ->count(),
        ];

        $typeStats = $user->notifications()
            ->selectRaw('type, COUNT(*) as count')
            ->groupBy('type')
            ->pluck('count', 'type')
            ->toArray();

        return response()->json([
            'message' => 'Lấy thống kê thông báo thành công',
            'data' => [
                'stats' => $stats,
                'by_type' => $typeStats,
            ],
        ]);
    }

    public function store(NotificationRequest $request)
    {
        $validated = $request->validated();

        $notification = $this->notificationService->create(
            (int) $validated['user_id'],
            $validated['type'],
            [
                'icon' => $validated['icon'] ?? null,
                'title' => $validated['title'],
                'message' => $validated['message'],
                'action_url' => $validated['action_url'] ?? null,
                'extra_data' => $validated['data'] ?? [],
            ]
        );

        return response()->json([
            'message' => 'Tạo thông báo thành công',
            'data' => [
                'notification' => $notification,
            ],
        ], 201);
    }
}
