# Groups API - Changelog & New Endpoints

Tài liệu này liệt kê các API mới đã được bổ sung vào backend để hỗ trợ đầy đủ tính năng Groups.

---

## ✅ CÁC API MỚI ĐÃ THÊM

### 1. **GET /api/groups/my-groups**
Lấy danh sách groups mà user hiện tại đã tham gia.

**Request:**
```http
GET /api/groups/my-groups
Headers: Authorization: Bearer {token}
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

### 2. **GET /api/groups/{groupId}/check-membership**
Kiểm tra trạng thái membership của user trong group.

**Request:**
```http
GET /api/groups/{groupId}/check-membership
Headers: Authorization: Bearer {token}
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

**Use Cases:**
- Frontend có thể check nhanh membership status
- Hiển thị đúng UI (Join/Leave button)
- Check permissions

---

### 3. **GET /api/groups/{groupId}/members**
Lấy danh sách members của group với filter và search.

**Request:**
```http
GET /api/groups/{groupId}/members?role=admin&search=john
Headers: Authorization: Bearer {token}
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

### 4. **GET /api/chat/threads/group/{groupId}**
Lấy hoặc tạo group chat thread.

**Request:**
```http
GET /api/chat/threads/group/{groupId}
Headers: Authorization: Bearer {token}
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
  ]
}
```

**Behavior:**
- Nếu thread chưa tồn tại → Tạo mới và thêm tất cả members vào participants
- Nếu thread đã tồn tại → Đảm bảo user hiện tại có trong participants
- Chỉ members mới access được

---

## 🔄 CÁC API ĐÃ CẬP NHẬT

### 1. **GET /api/groups** (Enhanced)
Thêm search, filter, sort và `posts_count`.

**New Query Parameters:**
- `search`: Search by name or description
- `visibility`: Filter by `public` or `private`
- `sort_by`: `latest` (default), `members`, `oldest`
- `per_page`: Items per page (default: 20)

**Response now includes:**
```json
{
  "data": [
    {
      "id": 1,
      "name": "Nhóm Học Laravel",
      "members_count": 25,
      "posts_count": 10,  // ← NEW
      ...
    }
  ],
  ...
}
```

---

### 2. **GET /api/groups/{slug}** (Enhanced)
Thêm `posts_count` và `owner` info.

**Response now includes:**
```json
{
  "id": 1,
  "name": "Nhóm Học Laravel",
  "members_count": 25,
  "posts_count": 10,  // ← NEW
  "owner": {          // ← NEW
    "id": 1,
    "name": "John Doe"
  },
  "members": [...],
  ...
}
```

---

### 3. **POST /api/groups** (Enhanced)
Auto-create group chat thread khi tạo group.

**Behavior:**
- Tạo group → Tạo chat thread → Thêm owner vào participants
- Thread được tạo tự động với:
  - `type = 'group'`
  - `name = group.name`
  - `group_id = group.id`
  - `owner_id = group.owner_id`

---

### 4. **POST /api/groups/{groupId}/join** (Enhanced)
Auto-join group chat thread khi join group.

**Behavior:**
- Join group → Tự động thêm vào chat participants
- Chỉ áp dụng cho public groups (private groups cần approve trước)

---

### 5. **POST /api/groups/{groupId}/leave** (Enhanced)
Auto-leave group chat thread khi leave group.

**Behavior:**
- Leave group → Tự động xóa khỏi chat participants
- Owner không thể leave (phải transfer ownership hoặc delete group)

---

### 6. **POST /api/groups/{groupId}/kick/{userId}** (Enhanced)
Auto-leave chat khi kick member.

**Behavior:**
- Kick member → Tự động xóa khỏi chat participants
- Không thể kick owner

---

### 7. **POST /api/groups/join-request/{requestId}/approve** (Enhanced)
Auto-join chat khi approve join request.

**Behavior:**
- Approve request → Add member → Tự động thêm vào chat participants

---

## 📋 TỔNG KẾT CÁC THAY ĐỔI

### Controllers Updated:
1. ✅ `ChatController` - Thêm `groupThread()` method
2. ✅ `GroupController` - Thêm `myGroups()`, `checkMembership()`, `members()`
3. ✅ `GroupController` - Update `index()`, `show()`, `store()`
4. ✅ `GroupMemberController` - Update `join()`, `leave()`, `kick()`
5. ✅ `GroupJoinRequestController` - Update `approve()`

### Models Updated:
1. ✅ `ChatThread` - Thêm relationship `group()`
2. ✅ `Group` - Thêm relationship `chatThread()`

### Routes Added:
1. ✅ `GET /api/groups/my-groups`
2. ✅ `GET /api/groups/{groupId}/check-membership`
3. ✅ `GET /api/groups/{groupId}/members`
4. ✅ `GET /api/chat/threads/group/{groupId}`

---

## 🎯 LỢI ÍCH

### 1. **Auto Chat Integration**
- Group chat thread được tạo tự động
- Members tự động join/leave chat
- Không cần manual setup

### 2. **Better UX**
- `checkMembership` API giúp frontend check nhanh status
- `myGroups` API để hiển thị groups của user
- `members` API với filter/search

### 3. **Complete Data**
- `posts_count` trong group response
- `owner` info trong group detail
- Search và filter options

### 4. **Security**
- Owner không thể leave group
- Owner không thể bị kick
- Private groups chỉ members access được

---

## 🔍 TESTING CHECKLIST

- [ ] Tạo group mới → Verify chat thread được tạo
- [ ] Join public group → Verify auto-join chat
- [ ] Leave group → Verify auto-leave chat
- [ ] Kick member → Verify auto-leave chat
- [ ] Approve join request → Verify auto-join chat
- [ ] Get my-groups → Verify chỉ trả về groups của user
- [ ] Check membership → Verify trả về đúng status
- [ ] Get members → Verify filter/search hoạt động
- [ ] Get group thread → Verify tạo mới nếu chưa có
- [ ] Owner leave group → Verify error message

---

## 📝 NOTES

### Migration cho Existing Groups
Nếu đã có groups trong database, cần tạo chat threads cho chúng:

```php
// Tạo migration hoặc command
$groups = Group::all();
foreach ($groups as $group) {
    $thread = ChatThread::firstOrCreate([
        'type' => 'group',
        'group_id' => $group->id,
    ], [
        'name' => $group->name,
        'owner_id' => $group->owner_id,
    ]);

    // Thêm tất cả members vào participants
    $members = GroupMember::where('group_id', $group->id)->pluck('user_id');
    foreach ($members as $userId) {
        ChatParticipant::firstOrCreate([
            'thread_id' => $thread->id,
            'user_id' => $userId,
        ]);
    }
}
```

---

**Tất cả các API đã được bổ sung và sẵn sàng sử dụng! 🎉**

