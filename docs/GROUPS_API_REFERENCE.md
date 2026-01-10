# Groups API Reference - Quick Reference Guide

Tài liệu tham khảo nhanh các API endpoints cho tính năng Groups.

---

## BASE URL

```
http://localhost:8000/api
```

**Authentication:** Bearer Token trong header `Authorization: Bearer {token}`

---

## GROUPS ENDPOINTS

### 1. Lấy danh sách Groups

```http
GET /api/groups?page=1&search=keyword&visibility=public&sort_by=latest&per_page=20
```

**Query Parameters:**
- `page` (optional): Page number (default: 1)
- `search` (optional): Search by name or description
- `visibility` (optional): Filter by `public` or `private`
- `sort_by` (optional): `latest` (default), `members`, `oldest`
- `per_page` (optional): Items per page (default: 20)

**Response:**
```json
{
  "data": [
    {
      "id": 1,
      "name": "Nhóm Học Laravel",
      "slug": "nhom-hoc-laravel-abc123",
      "description": "Nhóm học tập Laravel",
      "cover_image": "https://...",
      "members_count": 25,
      "posts_count": 10,
      "owner_id": 1,
      "visibility": "public",
      "created_at": "2024-01-01T00:00:00.000000Z",
      "updated_at": "2024-01-01T00:00:00.000000Z"
    }
  ],
  "current_page": 1,
  "last_page": 5,
  "per_page": 20,
  "total": 100
}
```

---

### 2. Tạo Group mới

```http
POST /api/groups
Content-Type: application/json

{
  "name": "Nhóm Học React",
  "description": "Nhóm học tập React và Next.js",
  "cover_image": "https://...",
  "visibility": "public"
}
```

**Response:**
```json
{
  "message": "Group created",
  "data": {
    "id": 2,
    "name": "Nhóm Học React",
    "slug": "nhom-hoc-react-xyz789",
    ...
  }
}
```

---

### 2. Lấy Groups của User hiện tại

```http
GET /api/groups/my-groups
```

**Response:**
```json
[
  {
    "id": 1,
    "name": "Nhóm Học Laravel",
    "slug": "nhom-hoc-laravel-abc123",
    "description": "Nhóm học tập Laravel",
    "cover_image": "https://...",
    "members_count": 25,
    "posts_count": 10,
    "owner_id": 1,
    "visibility": "public",
    "owner": {
      "id": 1,
      "name": "John Doe"
    },
    "created_at": "2024-01-01T00:00:00.000000Z",
    "updated_at": "2024-01-01T00:00:00.000000Z"
  }
]
```

---

### 3. Xem chi tiết Group

```http
GET /api/groups/{slug}
```

**Response:**
```json
{
  "id": 1,
  "name": "Nhóm Học Laravel",
  "slug": "nhom-hoc-laravel-abc123",
  "description": "Nhóm học tập Laravel",
  "cover_image": "https://...",
  "members_count": 25,
  "posts_count": 10,
  "owner_id": 1,
  "visibility": "public",
  "owner": {
    "id": 1,
    "name": "John Doe"
  },
  "members": [
    {
      "id": 1,
      "group_id": 1,
      "user_id": 1,
      "role": "owner",
      "user": {
        "id": 1,
        "name": "John Doe"
      }
    },
    {
      "id": 2,
      "group_id": 1,
      "user_id": 2,
      "role": "member",
      "user": {
        "id": 2,
        "name": "Jane Smith"
      }
    }
  ]
}
```

---

### 4. Cập nhật Group (Owner only)

```http
PUT /api/groups/{groupId}
Content-Type: application/json

{
  "description": "Mô tả mới",
  "cover_image": "https://...",
  "visibility": "private"
}
```

**Response:**
```json
{
  "message": "Group updated"
}
```

---

### 5. Xóa Group (Owner only)

```http
DELETE /api/groups/{groupId}
```

**Response:**
```json
{
  "message": "Group deleted"
}
```

---

### 4. Kiểm tra Membership Status

```http
GET /api/groups/{groupId}/check-membership
```

**Response:**
```json
{
  "is_member": true,
  "role": "member",
  "is_owner": false,
  "has_pending_request": false
}
```

---

### 5. Lấy danh sách Members

```http
GET /api/groups/{groupId}/members?role=admin&search=john
```

**Query Parameters:**
- `role` (optional): Filter by role (`owner`, `admin`, `member`)
- `search` (optional): Search by user name

**Response:**
```json
[
  {
    "id": 1,
    "group_id": 1,
    "user_id": 1,
    "role": "owner",
    "user": {
      "id": 1,
      "name": "John Doe"
    }
  },
  {
    "id": 2,
    "group_id": 1,
    "user_id": 2,
    "role": "member",
    "user": {
      "id": 2,
      "name": "Jane Smith"
    }
  }
]
```

**Permissions:**
- Public group: Mọi người xem được
- Private group: Chỉ members xem được

---

## GROUP MEMBERS ENDPOINTS

### 6. Tham gia Group

**Lưu ý:** Tự động join group chat thread khi join group thành công.

```http
POST /api/groups/{groupId}/join
```

**Response:**
- Public group: `{ "message": "Joined group" }`
- Private group: `{ "message": "Join request sent" }` (status: 202)

---

### 7. Rời Group

**Lưu ý:** 
- Tự động leave group chat thread khi leave group
- Owner không thể leave group (phải transfer ownership hoặc delete group)

```http
POST /api/groups/{groupId}/leave
```

**Response:**
```json
{
  "message": "Left group"
}
```

---

### 8. Xóa Member (Owner/Admin only)

**Lưu ý:** Tự động leave group chat thread khi bị kick. Không thể kick owner.

```http
POST /api/groups/{groupId}/kick/{userId}
```

**Response:**
```json
{
  "message": "Member removed"
}
```

---

### 9. Thăng Admin (Owner/Admin only)

```http
POST /api/groups/{groupId}/promote/{userId}
```

**Response:**
```json
{
  "message": "Promoted to admin"
}
```

---

### 10. Hạ Admin về Member (Owner only)

```http
POST /api/groups/{groupId}/demote/{userId}
```

**Response:**
```json
{
  "message": "Demoted to member"
}
```

---

## JOIN REQUESTS ENDPOINTS (Private Groups)

### 11. Lấy danh sách Join Requests (Admin/Owner only)

```http
GET /api/groups/{groupId}/join-requests
```

**Response:**
```json
[
  {
    "id": 1,
    "group_id": 1,
    "user_id": 5,
    "status": "pending",
    "user": {
      "id": 5,
      "name": "New User"
    }
  }
]
```

---

### 12. Duyệt Join Request (Admin/Owner only)

```http
POST /api/groups/join-request/{requestId}/approve
```

**Response:**
```json
{
  "message": "Request approved"
}
```

---

### 13. Từ chối Join Request (Admin/Owner only)

```http
POST /api/groups/join-request/{requestId}/reject
```

**Response:**
```json
{
  "message": "Request rejected"
}
```

---

## GROUP POSTS ENDPOINTS

### 14. Lấy Posts của Group

```http
GET /api/posts/group/{groupId}?page=1
```

**Response:**
```json
{
  "data": [
    {
      "id": 1,
      "user_id": 1,
      "content": "Bài viết đầu tiên",
      "attachments": ["https://..."],
      "group_id": 1,
      "is_pinned": false,
      "visibility": "group_only",
      "created_at": "2024-01-01T00:00:00.000000Z",
      "updated_at": "2024-01-01T00:00:00.000000Z",
      "user": {
        "id": 1,
        "name": "John Doe"
      },
      "comments_count": 5,
      "reactions_count": 10,
      "reactions": [
        {
          "id": 1,
          "user_id": 2,
          "reaction_type": "like"
        }
      ]
    }
  ],
  "current_page": 1,
  "last_page": 3,
  "per_page": 10,
  "total": 25
}
```

---

### 15. Tạo Post trong Group

```http
POST /api/posts
Content-Type: application/json

{
  "content": "Nội dung bài viết",
  "attachments": ["https://..."],
  "group_id": 1,
  "visibility": "group_only"
}
```

**Response:**
```json
{
  "message": "Post created successfully",
  "data": {
    "id": 2,
    "user_id": 1,
    "content": "Nội dung bài viết",
    ...
  }
}
```

---

## GROUP CHAT ENDPOINTS

### 16. Lấy Group Chat Thread

```http
GET /api/chat/threads/group/{groupId}
```

**Response:**
```json
{
  "id": 5,
  "type": "group",
  "name": "Nhóm Học Laravel",
  "group_id": 1,
  "owner_id": 1,
  "created_at": "2024-01-01T00:00:00.000000Z",
  "updated_at": "2024-01-01T00:00:00.000000Z",
  "participants": [
    {
      "id": 1,
      "thread_id": 5,
      "user_id": 1,
      "last_read_at": null,
      "user": {
        "id": 1,
        "name": "John Doe"
      }
    }
  ],
  "group": {
    "id": 1,
    "name": "Nhóm Học Laravel",
    "slug": "nhom-hoc-laravel-abc123"
  }
}
```

**Behavior:**
- Nếu thread chưa tồn tại → Tạo mới và thêm tất cả members vào participants
- Nếu thread đã tồn tại → Đảm bảo user hiện tại có trong participants
- Chỉ members mới access được

---

### 17. Lấy tất cả Threads (bao gồm Group threads)

```http
GET /api/chat/threads
```

**Response:**
```json
[
  {
    "id": 1,
    "type": "direct",
    "name": null,
    "group_id": null,
    "participants": [...]
  },
  {
    "id": 2,
    "type": "group",
    "name": "Nhóm Học Laravel",
    "group_id": 1,
    "participants": [...]
  }
]
```

**LƯU Ý:** Backend hiện chưa có endpoint riêng để lấy group thread. Cần filter từ response này:
- `type === 'group'`
- `group_id === {groupId}`

---

### 18. Lấy Messages của Thread

```http
GET /api/chat/threads/{threadId}/messages?limit=30&page=1
```

**Response:**
```json
{
  "data": [
    {
      "id": 1,
      "thread_id": 2,
      "user_id": 1,
      "content": "Xin chào mọi người!",
      "attachments": null,
      "created_at": "2024-01-01T00:00:00.000000Z",
      "user": {
        "id": 1,
        "name": "John Doe"
      }
    }
  ],
  "current_page": 1,
  "last_page": 1,
  "per_page": 30,
  "total": 1
}
```

---

### 19. Gửi Message

```http
POST /api/chat/threads/{threadId}/messages
Content-Type: application/json

{
  "content": "Tin nhắn mới",
  "attachments": ["https://..."]
}
```

**Response:**
```json
{
  "id": 2,
  "thread_id": 2,
  "user_id": 1,
  "content": "Tin nhắn mới",
  "attachments": null,
  "created_at": "2024-01-01T00:00:00.000000Z",
  "user": {
    "id": 1,
    "name": "John Doe"
  }
}
```

---

### 20. Đánh dấu đã đọc

```http
POST /api/chat/threads/{threadId}/read
```

**Response:**
```json
{
  "message": "Marked as read"
}
```

---

### 21. Typing Indicator

```http
POST /api/chat/threads/{threadId}/typing
```

**Response:**
```json
{
  "typing": true
}
```

---

## ERROR RESPONSES

### 401 Unauthorized
```json
{
  "message": "Unauthenticated."
}
```

### 403 Forbidden
```json
{
  "message": "Forbidden"
}
```

### 404 Not Found
```json
{
  "message": "Group not found"
}
```

### 422 Validation Error
```json
{
  "message": "The given data was invalid.",
  "errors": {
    "name": ["The name field is required."]
  }
}
```

---

## SHARE POST ENDPOINT

### 22. Share Post

```http
POST /api/posts/{postId}/share
Content-Type: application/json
Authorization: Bearer {token}

{
  "content": "Chia sẻ bài viết hay!",
  "group_id": 1,
  "visibility": "group_only"
}
```

**Response:**
```json
{
  "message": "Post shared successfully",
  "data": {
    "id": 10,
    "user_id": 2,
    "content": "Chia sẻ bài viết hay!",
    "group_id": 1,
    "target_type": "App\\Models\\Post",
    "target_id": 5,
    "visibility": "group_only",
    "created_at": "2024-01-01T00:00:00.000000Z",
    "user": {
      "id": 2,
      "name": "Jane Smith"
    },
    "target": {
      "id": 5,
      "content": "Bài viết gốc",
      "user": {
        "id": 1,
        "name": "John Doe"
      }
    }
  }
}
```

---

## REALTIME ENDPOINTS

### 23. Get Realtime Connection Info

```http
GET /api/realtime/connection-info
Authorization: Bearer {token}
```

**Response:**
```json
{
  "connection": {
    "app_key": "your-app-key",
    "host": "localhost",
    "port": 8080,
    "scheme": "ws"
  },
  "channels": {
    "global": "posts.global",
    "groups": ["group.1.posts", "group.2.posts"],
    "chats": ["chat.thread.1", "chat.thread.2"]
  },
  "user_id": 1
}
```

### 24. Test Realtime Connection

```http
POST /api/realtime/test-connection
Authorization: Bearer {token}
```

**Response:**
```json
{
  "message": "Test event broadcasted",
  "user": {
    "id": 1,
    "name": "John Doe"
  }
}
```

---

## NOTES

### Backend Requirements (✅ Đã hoàn thành)

1. ✅ **Auto-create Group Chat Thread:**
   - Khi tạo group → Tự động tạo `ChatThread` với:
     - `type = 'group'`
     - `group_id = {groupId}`
     - `name = {group.name}`
   - Thêm owner vào `ChatParticipant`

2. ✅ **Auto-join Chat khi Join Group:**
   - Khi user join group → Tự động thêm vào `ChatParticipant` của group thread

3. ✅ **Auto-leave Chat khi Leave Group:**
   - Khi user leave group → Tự động xóa khỏi `ChatParticipant`

4. ✅ **API Endpoint mới:**
   ```
   GET /api/chat/threads/group/{groupId}
   GET /api/groups/my-groups
   GET /api/groups/{groupId}/check-membership
   GET /api/groups/{groupId}/members
   ```

5. ✅ **Posts Count:**
   - Đã thêm `posts_count` vào Group response (sử dụng `withCount('posts')`)

---

## REALTIME EVENTS (Laravel Echo)

### Channels

**Chat Channels:**
```
chat.thread.{threadId}
```

**Posts Channels:**
```
posts.global                    // Global posts feed
group.{groupId}.posts          // Group-specific posts
```

### Chat Events

**1. message.created**
```json
{
  "message": {
    "id": 1,
    "thread_id": 2,
    "user_id": 1,
    "content": "Tin nhắn mới",
    "created_at": "2024-01-01T00:00:00.000000Z",
    "user": {
      "id": 1,
      "name": "John Doe"
    }
  },
  "threadId": 2
}
```

**2. thread.read**
```json
{
  "threadId": 2,
  "userId": 1,
  "userName": "John Doe"
}
```

**3. user.typing**
```json
{
  "threadId": 2,
  "userId": 1,
  "userName": "John Doe"
}
```

### Posts Events

**4. post.created**
```json
{
  "post": {
    "id": 1,
    "content": "Bài viết mới",
    "user": { "id": 1, "name": "John Doe" },
    "group": { "id": 1, "name": "Laravel Group" }
  },
  "type": "post_created"
}
```

**5. post.reacted**
```json
{
  "post_id": 1,
  "reaction": {
    "id": 1,
    "user": { "id": 2, "name": "Jane Smith" },
    "reaction_type": "like"
  },
  "action": "added",
  "reactions_count": 5,
  "type": "post_reacted"
}
```

**6. post.shared**
```json
{
  "original_post": { ... },
  "shared_post": { ... },
  "shared_by": { "id": 3, "name": "Bob Wilson" },
  "type": "post_shared"
}
```

**7. comment.created**
```json
{
  "comment": {
    "id": 1,
    "content": "Bình luận mới",
    "user": { "id": 2, "name": "Jane Smith" }
  },
  "post_id": 1,
  "comments_count": 3,
  "type": "comment_created"
}
```

**8. comment.reacted**
```json
{
  "comment_id": 1,
  "post_id": 1,
  "reaction": {
    "id": 2,
    "user": { "id": 3, "name": "Bob Wilson" },
    "reaction_type": "love"
  },
  "action": "added",
  "reactions_count": 2,
  "type": "comment_reacted"
}
```

**9. test.connection**
```json
{
  "message": "Test realtime connection",
  "user": { "id": 1, "name": "John Doe" },
  "timestamp": "2024-01-01T00:00:00.000000Z",
  "type": "test_connection"
}
```

---

## USAGE EXAMPLES

### TypeScript/JavaScript

```typescript
import axios from 'axios';

const api = axios.create({
  baseURL: 'http://localhost:8000/api',
  headers: {
    Authorization: `Bearer ${token}`,
  },
});

// Get groups
const groups = await api.get('/groups');

// Join group
await api.post(`/groups/${groupId}/join`);

// Get group detail
const group = await api.get(`/groups/${slug}`);

// Create post
await api.post('/posts', {
  content: 'Hello!',
  group_id: 1,
});
```

---

**Tài liệu này cung cấp tham khảo nhanh. Xem `GROUPS_UI_PROMPT.md` và `GROUPS_IMPLEMENTATION_GUIDE.md` để biết chi tiết hơn.**
