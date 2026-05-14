<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Notification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class NotificationController extends Controller
{
    /**
     * Lấy danh sách notifications của user hiện tại
     */
    public function index(Request $request)
    {
        $perPage = $request->get('per_page', 15);
        $type = $request->get('type'); // filter theo type nếu cần
        
        $query = Auth::user()->notifications()
            ->when($type, function ($q) use ($type) {
                $q->where('type', $type);
            })
            ->orderBy('created_at', 'desc');

        $notifications = $query->paginate($perPage);

        return response()->json([
            'message' => 'Lấy danh sách thông báo thành công',
            'data' => [
                'notifications' => $notifications->items(),
                'pagination' => [
                    'current_page' => $notifications->currentPage(),
                    'last_page' => $notifications->lastPage(),
                    'per_page' => $notifications->perPage(),
                    'total' => $notifications->total(),
                    'has_more' => $notifications->hasMorePages()
                ]
            ]
        ]);
    }

    /**
     * Lấy số lượng notifications chưa đọc
     */
    public function unreadCount()
    {
        $count = Auth::user()->notifications()
            ->unread()
            ->count();

        return response()->json([
            'message' => 'Lấy số thông báo chưa đọc thành công',
            'data' => [
                'unread_count' => $count
            ]
        ]);
    }

    /**
     * Lấy notifications chưa đọc
     */
    public function unread(Request $request)
    {
        $limit = $request->get('limit', 10);
        
        $notifications = Auth::user()->notifications()
            ->unread()
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();

        return response()->json([
            'message' => 'Lấy thông báo chưa đọc thành công',
            'data' => [
                'notifications' => $notifications,
                'count' => $notifications->count()
            ]
        ]);
    }

    /**
     * Xem chi tiết một notification
     */
    public function show($id)
    {
        $notification = Auth::user()->notifications()->find($id);

        if (!$notification) {
            return response()->json([
                'message' => 'Không tìm thấy thông báo'
            ], 404);
        }

        // Auto mark as read khi xem chi tiết
        if (!$notification->is_read) {
            $notification->markAsRead();
        }

        return response()->json([
            'message' => 'Lấy chi tiết thông báo thành công',
            'data' => [
                'notification' => $notification->fresh()
            ]
        ]);
    }

    /**
     * Đánh dấu một notification đã đọc
     */
    public function markAsRead($id)
    {
        $notification = Auth::user()->notifications()->find($id);

        if (!$notification) {
            return response()->json([
                'message' => 'Không tìm thấy thông báo'
            ], 404);
        }

        if ($notification->is_read) {
            return response()->json([
                'message' => 'Thông báo đã được đánh dấu đọc trước đó',
                'data' => [
                    'notification' => $notification
                ]
            ]);
        }

        $notification->markAsRead();

        return response()->json([
            'message' => 'Đánh dấu đã đọc thành công',
            'data' => [
                'notification' => $notification->fresh()
            ]
        ]);
    }

    /**
     * Đánh dấu tất cả notifications đã đọc
     */
    public function markAllAsRead()
    {
        $updatedCount = Auth::user()->notifications()
            ->unread()
            ->update(['read_at' => now()]);

        return response()->json([
            'message' => "Đã đánh dấu {$updatedCount} thông báo là đã đọc",
            'data' => [
                'updated_count' => $updatedCount
            ]
        ]);
    }

    /**
     * Xóa một notification
     */
    public function destroy($id)
    {
        $notification = Auth::user()->notifications()->find($id);

        if (!$notification) {
            return response()->json([
                'message' => 'Không tìm thấy thông báo'
            ], 404);
        }

        $notification->delete();

        return response()->json([
            'message' => 'Xóa thông báo thành công'
        ]);
    }

    /**
     * Xóa tất cả notifications đã đọc
     */
    public function clearRead()
    {
        $deletedCount = Auth::user()->notifications()
            ->whereNotNull('read_at')
            ->delete();

        return response()->json([
            'message' => "Đã xóa {$deletedCount} thông báo đã đọc",
            'data' => [
                'deleted_count' => $deletedCount
            ]
        ]);
    }

    /**
     * Lấy thống kê notifications
     */
    public function stats()
    {
        $userId = Auth::id();
        
        $stats = [
            'total' => Auth::user()->notifications()->count(),
            'unread' => Auth::user()->notifications()->unread()->count(),
            'read' => Auth::user()->notifications()->whereNotNull('read_at')->count(),
            'today' => Auth::user()->notifications()->whereDate('created_at', today())->count(),
            'this_week' => Auth::user()->notifications()->whereBetween('created_at', [
                now()->startOfWeek(),
                now()->endOfWeek()
            ])->count(),
        ];

        // Thống kê theo type
        $typeStats = Auth::user()->notifications()
            ->selectRaw('type, COUNT(*) as count')
            ->groupBy('type')
            ->pluck('count', 'type')
            ->toArray();

        return response()->json([
            'message' => 'Lấy thống kê thông báo thành công',
            'data' => [
                'stats' => $stats,
                'by_type' => $typeStats
            ]
        ]);
    }

    /**
     * Tạo notification mới (for testing/admin)
     */
    public function store(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'type' => 'required|string|max:255',
            'title' => 'required|string|max:255',
            'message' => 'required|string',
            'data' => 'nullable|array'
        ]);

        $notification = Notification::create([
            'user_id' => $request->user_id,
            'type' => $request->type,
            'data' => [
                'title' => $request->title,
                'message' => $request->message,
                'extra_data' => $request->data ?? []
            ]
        ]);

        return response()->json([
            'message' => 'Tạo thông báo thành công',
            'data' => [
                'notification' => $notification
            ]
        ], 201);
    }
}