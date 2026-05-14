# Notification System - Frontend Implementation

## Yêu Cầu Tổng Quan
Tạo hệ thống thông báo frontend tích hợp với Laravel API, hiển thị notifications realtime với dropdown/sidebar, quản lý trạng thái đọc/chưa đọc.

## API Endpoints

### Base URL: `/api/notifications`
**Headers:** `Authorization: Bearer {token}`

#### **Đọc Notifications**
```
GET /api/notifications?per_page=15&type=new_message
GET /api/notifications/unread?limit=10
GET /api/notifications/unread-count
GET /api/notifications/{id}
GET /api/notifications/stats
```

#### **Quản Lý**
```
POST /api/notifications/{id}/read
POST /api/notifications/mark-all-read
DELETE /api/notifications/{id}
DELETE /api/notifications/clear-read
```

## Response Format

### **Notification Object**
```json
{
  "id": 1,
  "type": "new_message",
  "data": {
    "title": "Tin nhắn mới",
    "message": "Bạn có tin nhắn mới từ John",
    "icon": "💬",
    "action_url": "/chat/threads/1",
    "extra_data": {...}
  },
  "read_at": null,
  "created_at": "2024-01-09T10:30:00Z"
}
```

### **List Response**
```json
{
  "message": "Success",
  "data": {
    "notifications": [...],
    "pagination": {
      "current_page": 1,
      "total": 25,
      "has_more": true
    }
  }
}
```

### **Unread Count**
```json
{
  "data": {
    "unread_count": 5
  }
}
```

## Notification Types

- `new_message` - Tin nhắn mới (💬)
- `joined_group` - Tham gia nhóm (👥)
- `course_completed` - Hoàn thành khóa học (🎉)
- `quiz_completed` - Hoàn thành quiz (🏆)
- `ai_quiz_generated` - AI tạo quiz (🤖)
- `token_reward` - Nhận thưởng token (🪙)
- `system_announcement` - Thông báo hệ thống (📢)

## Frontend Requirements

### **1. Notification Bell/Icon**
- Hiển thị unread count badge
- Click mở dropdown/sidebar
- Auto refresh count mỗi 30s

### **2. Notification List**
- Hiển thị title, message, icon, time
- Phân biệt đã đọc/chưa đọc (styling)
- Infinite scroll hoặc pagination
- Empty state khi không có notifications

### **3. Actions**
- Click notification → mark as read + navigate to action_url
- "Mark all as read" button
- "Clear read" button
- Delete individual notification

### **4. Real-time Updates**
- Listen for new notifications (WebSocket/SSE)
- Update unread count realtime
- Show toast/popup cho notifications mới

## State Management

```typescript
interface NotificationState {
  notifications: Notification[];
  unreadCount: number;
  isLoading: boolean;
  hasMore: boolean;
  currentPage: number;
}

interface Notification {
  id: number;
  type: string;
  data: {
    title: string;
    message: string;
    icon: string;
    action_url?: string;
    extra_data?: any;
  };
  read_at: string | null;
  created_at: string;
  is_read: boolean;
}
```

## API Integration

### **Fetch Notifications**
```typescript
const fetchNotifications = async (page = 1) => {
  const response = await api.get(`/notifications?per_page=15&page=${page}`);
  return response.data;
};
```

### **Mark as Read**
```typescript
const markAsRead = async (id: number) => {
  await api.post(`/notifications/${id}/read`);
  // Update local state
};
```

### **Get Unread Count**
```typescript
const getUnreadCount = async () => {
  const response = await api.get('/notifications/unread-count');
  return response.data.data.unread_count;
};
```

## User Experience

### **Notification Flow**
1. User nhận notification mới → unread count tăng
2. Click notification bell → mở list
3. Click notification item → mark as read + navigate
4. Notification styling thay đổi (đã đọc)

### **Auto Actions**
- Auto mark as read khi click vào notification
- Auto refresh unread count
- Auto scroll to top khi có notification mới

### **Loading States**
- Skeleton loader cho notification list
- Loading spinner cho actions
- Optimistic updates cho mark as read

## Integration Points

### **1. Header/Navbar**
- Notification bell icon với badge
- Position: top-right thường

### **2. Routing**
- Handle action_url navigation
- Deep linking support

### **3. Toast/Popup**
- Show new notifications as toast
- Auto dismiss sau 5s
- Click toast → mở notification detail

## Performance

- **Pagination**: Load 15 notifications per page
- **Caching**: Cache notifications trong 5 phút
- **Debounce**: Debounce mark as read calls
- **Virtual Scrolling**: Cho lists dài

## Error Handling

- Network errors → retry mechanism
- API errors → user-friendly messages
- Offline support → queue actions

---

**Mục tiêu**: Tạo notification system mượt mà, realtime, user-friendly với đầy đủ tính năng quản lý và tích hợp tốt với hệ thống hiện có.