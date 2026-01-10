# PROMPT: Tạo Giao Diện Groups (Nhóm Học Tập) với Next.js 15

## YÊU CẦU TỔNG QUAN

Tạo một hệ thống quản lý nhóm học tập (Study Groups) hoàn chỉnh với Next.js 15 (App Router), TypeScript, Tailwind CSS, và tích hợp chat realtime. Hệ thống bao gồm: khám phá nhóm, chi tiết nhóm, feed bài viết, quản lý thành viên, và chat nhóm realtime.

---

## KIẾN TRÚC BACKEND API

### Base URL
- API Base: `http://localhost:8000/api` (hoặc domain production)
- Broadcasting: Laravel Reverb (WebSocket)
- Authentication: Laravel Sanctum (Bearer Token)

### API Endpoints

#### 1. Groups Listing - Lấy danh sách tất cả nhóm
```
GET /api/groups?page=1
Headers: Authorization: Bearer {token}
Response: {
  data: Array<Group>;
  current_page: number;
  last_page: number;
  per_page: number;
  total: number;
}
```

**Group Structure:**
```typescript
interface Group {
  id: number;
  name: string;
  slug: string;
  description: string | null;
  cover_image: string | null;
  members_count: number;
  owner_id: number;
  visibility: 'public' | 'private';
  created_at: string;
  updated_at: string;
  owner?: {
    id: number;
    name: string;
  };
  members_count?: number; // từ withCount
}
```

#### 2. Tạo nhóm mới
```
POST /api/groups
Headers: Authorization: Bearer {token}
Body: {
  name: string;
  description?: string;
  cover_image?: string;
  visibility: 'public' | 'private';
}
Response: {
  message: string;
  data: Group;
}
```

#### 3. Xem chi tiết nhóm
```
GET /api/groups/{slug}
Headers: Authorization: Bearer {token}
Response: Group (với members.user)
```

**Response bao gồm:**
```typescript
interface GroupDetail extends Group {
  members: Array<{
    id: number;
    group_id: number;
    user_id: number;
    role: 'owner' | 'admin' | 'member';
    user: {
      id: number;
      name: string;
    };
  }>;
}
```

#### 4. Cập nhật nhóm (chỉ owner)
```
PUT /api/groups/{groupId}
Headers: Authorization: Bearer {token}
Body: {
  description?: string;
  cover_image?: string;
  visibility?: 'public' | 'private';
}
Response: { message: string }
```
 
#### 5. Xóa nhóm (chỉ owner)
```
DELETE /api/groups/{groupId}
Headers: Authorization: Bearer {token}
Response: { message: string }
```

#### 6. Tham gia nhóm
```
POST /api/groups/{groupId}/join
Headers: Authorization: Bearer {token}
Response: { message: string }
```
- Public group: Join trực tiếp
- Private group: Gửi join request (status: pending)

#### 7. Rời nhóm
```
POST /api/groups/{groupId}/leave
Headers: Authorization: Bearer {token}
Response: { message: string }
```

#### 8. Quản lý thành viên (owner/admin)
```
POST /api/groups/{groupId}/kick/{userId}      // Xóa thành viên
POST /api/groups/{groupId}/promote/{userId}   // Thăng admin
POST /api/groups/{groupId}/demote/{userId}   // Hạ admin về member
```

#### 9. Quản lý join requests (private groups)
```
GET /api/groups/{groupId}/join-requests
POST /api/groups/join-request/{requestId}/approve
POST /api/groups/join-request/{requestId}/reject
```

#### 10. Group Posts Feed
```
GET /api/posts/group/{groupId}?page=1
Headers: Authorization: Bearer {token}
Response: PaginatedResponse<Post>
```

**Post Structure:**
```typescript
interface Post {
  id: number;
  user_id: number;
  content: string | null;
  attachments: string[] | null;
  group_id: number | null;
  is_pinned: boolean;
  visibility: 'public' | 'private' | 'group_only';
  created_at: string;
  updated_at: string;
  user: {
    id: number;
    name: string;
  };
  comments_count: number;
  reactions_count: number;
  reactions?: Array<{
    id: number;
    user_id: number;
    reaction_type: string;
  }>;
}
```

#### 11. Tạo post trong nhóm
```
POST /api/posts
Headers: Authorization: Bearer {token}
Body: {
  content?: string;
  attachments?: string[];
  group_id: number;
  visibility?: 'public' | 'private' | 'group_only';
}
Response: { message: string; data: Post }
```

---

## TÍCH HỢP GROUP CHAT

### Kiến trúc Chat Thread cho Groups

Mỗi group sẽ có một `ChatThread` với:
- `type: 'group'`
- `group_id: {groupId}`
- `name: {group.name}` (tên nhóm)

### API Endpoints cho Group Chat

**LƯU Ý:** Backend hiện tại chưa có endpoint riêng để tạo/lấy group thread. Cần bổ sung:

```
POST /api/chat/threads/group
Body: { group_id: number }
Response: ChatThread

HOẶC

GET /api/chat/threads/group/{groupId}
Response: ChatThread
```

**Tạm thời, có thể sử dụng:**
- `GET /api/chat/threads` - Lấy tất cả threads của user, filter theo `type === 'group'` và `group_id === {groupId}`
- Nếu chưa có thread, cần tạo mới (có thể làm ở backend khi user join group)

### Chat Thread Structure cho Group
```typescript
interface GroupChatThread extends ChatThread {
  type: 'group';
  group_id: number;
  name: string; // Tên nhóm
  participants: ChatParticipant[]; // Tất cả members của group
}
```

### Realtime Events (giống direct chat)
- Channel: `chat.thread.{threadId}`
- Events:
  - `.message.created` - Tin nhắn mới
  - `.thread.read` - User đánh dấu đã đọc
  - `.user.typing` - User đang gõ

---

## YÊU CẦU GIAO DIỆN

### 1. Groups Listing Page (`/groups`)

**Mục tiêu:** Người dùng xem danh sách tất cả nhóm có thể tham gia.

**Layout:**
- Header với search bar và filter (public/private, sort by members, date, etc.)
- Grid/List view toggle
- Pagination

**Group Card Component:**
- Avatar/Cover image (fallback nếu không có)
- Tên nhóm (link đến detail page)
- Mô tả (truncate nếu quá dài)
- Badge visibility (Public/Private)
- Số thành viên (`members_count`)
- Số bài viết (cần tính từ posts hoặc thêm field `posts_count`)
- Nút "Join" hoặc "Đã tham gia" (check `GroupMember` của current user)
- Owner info (tùy chọn)

**UI/UX:**
- Hover effect trên card
- Loading skeleton khi fetch
- Empty state khi chưa có nhóm
- Responsive: Mobile → 1 column, Tablet → 2 columns, Desktop → 3-4 columns

**Features:**
- Search groups by name
- Filter: Public only, Private only, My groups
- Sort: Most members, Newest, Oldest
- Infinite scroll hoặc pagination

---

### 2. Group Detail Page (`/groups/[slug]`)

**Layout:**
- Header với banner/cover image
- Group info section
- Tab navigation: Posts | Members | Chat
- Content area theo tab

**Header Section:**
- Cover image (full width, có thể edit nếu là owner)
- Avatar nhóm (overlay trên cover, bottom-left)
- Tên nhóm (large, bold)
- Mô tả
- Stats: Members count, Posts count
- Action buttons:
  - "Join Group" / "Leave Group" / "Pending Request" (tùy trạng thái)
  - "Edit Group" (chỉ owner)
  - "Settings" (owner/admin)

**Tab: Posts**
- Feed của nhóm (tương tự Facebook group feed)
- Component: `PostFeed`
  - Create post form (chỉ members)
  - List posts với:
    - User avatar + name
    - Content + attachments
    - Reactions (like, love, etc.)
    - Comments count
    - Timestamp
    - Actions: React, Comment, Share (nếu có)
  - Infinite scroll
  - Empty state: "Chưa có bài viết nào"

**Tab: Members**
- Component: `MembersList`
  - Grid/List view
  - Mỗi member card:
    - Avatar
    - Name
    - Role badge (Owner, Admin, Member)
    - Actions (nếu là owner/admin):
      - Kick
      - Promote/Demote
  - Search members
  - Filter by role
  - Empty state

**Tab: Chat**
- Component: `GroupChat` (tái sử dụng từ chat system)
- Hiển thị chat window tương tự direct chat
- Header: Tên nhóm + số participants
- Messages list với avatar + name (vì là group chat)
- Input area
- Typing indicators
- Read receipts (optional cho group chat)

**Permissions:**
- Public group: Mọi người xem được, chỉ members mới post/chat
- Private group: Chỉ members xem được
- Owner/Admin: Có quyền quản lý

---

### 3. Group Chat Integration trong Sidebar Chat

**Mục tiêu:** Khi user đã tham gia group, hiển thị chat nhóm trong sidebar chat chung.

**Sidebar Chat Structure:**
```
Chat Sidebar
├── Direct Messages
│   ├── User A
│   ├── User B
│   └── ...
└── Group Chats
    ├── Group 1
    ├── Group 2
    └── ...
```

**Group Chat Item trong Sidebar:**
- Avatar nhóm (hoặc icon group)
- Tên nhóm
- Preview tin nhắn cuối cùng
- Timestamp
- Unread count badge
- Last message sender name (nếu có)

**Logic:**
- Lấy tất cả threads từ `GET /api/chat/threads`
- Filter threads có `type === 'group'`
- Hiển thị trong section "Group Chats"
- Click vào → Navigate đến `/groups/[slug]?tab=chat` hoặc mở chat window

**Unread Count:**
- Tính từ `last_read_at` trong `ChatParticipant`
- So sánh với `last message.created_at` trong thread
- Hiển thị badge số tin nhắn chưa đọc

---

## TECHNICAL REQUIREMENTS

### State Management
- Groups list state
- Current group detail state
- Group members state
- Group posts state
- Group chat thread state
- Join requests state (nếu là admin)
- User membership status (đã join chưa, role gì)

### Hooks cần tạo

**`hooks/useGroups.ts`**
```typescript
function useGroups(filters?: {
  search?: string;
  visibility?: 'public' | 'private';
  sort?: 'members' | 'newest' | 'oldest';
}) {
  // Fetch groups list
  // Pagination
  // Join/Leave actions
  return { groups, isLoading, joinGroup, leaveGroup, ... };
}
```

**`hooks/useGroupDetail.ts`**
```typescript
function useGroupDetail(slug: string) {
  // Fetch group detail
  // Check membership
  // Join/Leave actions
  return { group, isMember, isOwner, isAdmin, joinGroup, leaveGroup, ... };
}
```

**`hooks/useGroupPosts.ts`**
```typescript
function useGroupPosts(groupId: number) {
  // Fetch posts
  // Create post
  // Infinite scroll
  return { posts, isLoading, createPost, loadMore, hasMore, ... };
}
```

**`hooks/useGroupMembers.ts`**
```typescript
function useGroupMembers(groupId: number) {
  // Fetch members
  // Kick, promote, demote actions
  return { members, isLoading, kickMember, promoteMember, ... };
}
```

**`hooks/useGroupChat.ts`**
```typescript
function useGroupChat(groupId: number) {
  // Get or create group thread
  // Fetch messages
  // Send message
  // Realtime updates
  return { thread, messages, sendMessage, ... };
}
```

**`hooks/useGroupJoinRequests.ts`**
```typescript
function useGroupJoinRequests(groupId: number) {
  // Fetch join requests (admin only)
  // Approve/Reject
  return { requests, isLoading, approve, reject, ... };
}
```

### Components Structure

```
components/groups/
├── GroupsList.tsx              # Main listing page
├── GroupCard.tsx               # Card trong list
├── GroupDetail.tsx            # Main detail page
├── GroupHeader.tsx             # Header với cover, info, actions
├── GroupTabs.tsx               # Tab navigation
├── GroupPostsTab.tsx           # Posts tab content
│   ├── PostFeed.tsx            # Feed container
│   ├── PostCard.tsx            # Single post
│   └── CreatePostForm.tsx      # Form tạo post
├── GroupMembersTab.tsx         # Members tab content
│   ├── MembersList.tsx         # List container
│   └── MemberCard.tsx          # Single member card
├── GroupChatTab.tsx            # Chat tab (reuse ChatWindow)
├── JoinRequestModal.tsx        # Modal approve/reject requests
└── GroupSettingsModal.tsx      # Settings (owner only)

components/chat/
├── ChatSidebar.tsx             # Updated với Group Chats section
├── GroupChatItem.tsx           # Item trong sidebar
└── ... (existing chat components)
```

### TypeScript Types

```typescript
// types/group.ts

export interface Group {
  id: number;
  name: string;
  slug: string;
  description: string | null;
  cover_image: string | null;
  members_count: number;
  owner_id: number;
  visibility: 'public' | 'private';
  created_at: string;
  updated_at: string;
  owner?: User;
}

export interface GroupDetail extends Group {
  members: GroupMember[];
}

export interface GroupMember {
  id: number;
  group_id: number;
  user_id: number;
  role: 'owner' | 'admin' | 'member';
  user: User;
}

export interface GroupJoinRequest {
  id: number;
  group_id: number;
  user_id: number;
  status: 'pending' | 'approved' | 'rejected';
  user: User;
}

export interface CreateGroupRequest {
  name: string;
  description?: string;
  cover_image?: string;
  visibility: 'public' | 'private';
}

export interface UpdateGroupRequest {
  description?: string;
  cover_image?: string;
  visibility?: 'public' | 'private';
}
```

---

## UI/UX GUIDELINES

### Colors
- Primary: Màu chủ đạo cho buttons, links
- Success: Join button, approved status
- Warning: Pending request
- Danger: Leave, kick actions
- Neutral: Cards, backgrounds

### Spacing
- Consistent padding/margin
- Card spacing: 16px-24px
- Section spacing: 32px-48px

### Responsive Breakpoints
- Mobile: < 768px (1 column)
- Tablet: 768px - 1024px (2 columns)
- Desktop: > 1024px (3-4 columns)

### Animations
- Smooth transitions
- Loading skeletons
- Hover effects
- Modal animations

### Empty States
- "Chưa có nhóm nào" với CTA "Tạo nhóm đầu tiên"
- "Chưa có bài viết nào" với CTA "Đăng bài đầu tiên"
- "Chưa có thành viên nào"

---

## FEATURES CHI TIẾT

### 1. Join/Leave Flow

**Public Group:**
1. Click "Join" → Call API → Update UI → Show "Leave" button
2. Auto join chat thread (backend xử lý)

**Private Group:**
1. Click "Join" → Send join request → Show "Pending Request"
2. Admin approve → User nhận notification → Auto join
3. Admin reject → User nhận notification → Reset button

### 2. Group Chat Flow

**Khi user join group:**
- Backend tự động tạo `ChatThread` với `type='group'`, `group_id={groupId}`
- Backend tự động thêm user vào `ChatParticipant`
- Frontend: Khi vào Group Detail → Tab Chat → Load thread

**Khi user leave group:**
- Backend tự động remove từ `ChatParticipant`
- Frontend: Remove thread khỏi sidebar

**Hiển thị trong sidebar:**
- Filter threads: `type === 'group'` và `group_id !== null`
- Group threads hiển thị trong section riêng
- Click → Navigate đến `/groups/[slug]?tab=chat`

### 3. Posts Feed

**Create Post:**
- Form với textarea, file upload
- Preview attachments
- Submit → Optimistic update → Refresh

**Display Posts:**
- Infinite scroll
- Group consecutive posts từ cùng user
- Reactions, comments
- Timestamp relative

### 4. Members Management

**Owner/Admin Actions:**
- Kick member (với confirmation modal)
- Promote to admin
- Demote admin to member
- View join requests (private groups)
- Approve/Reject requests

**UI:**
- Role badges
- Action dropdown menu
- Confirmation modals

---

## TESTING SCENARIOS

1. **Groups Listing**
   - Load danh sách groups
   - Search, filter, sort
   - Pagination/infinite scroll
   - Join/Leave từ listing

2. **Group Detail**
   - Load group info
   - Switch tabs
   - Join/Leave group
   - View posts, members, chat

3. **Group Posts**
   - Create post
   - View posts feed
   - React, comment
   - Infinite scroll

4. **Group Members**
   - View members list
   - Kick, promote, demote (admin)
   - Search members

5. **Group Chat**
   - Load chat thread
   - Send/receive messages
   - Typing indicators
   - Unread count

6. **Join Requests (Private)**
   - Send request
   - View requests (admin)
   - Approve/Reject

7. **Sidebar Integration**
   - Group chats hiển thị trong sidebar
   - Unread count
   - Navigate từ sidebar

---

## DELIVERABLES

1. **Complete Next.js 15 application** với:
   - TypeScript types
   - All components
   - Hooks và utilities
   - Error handling
   - Loading states
   - Responsive design

2. **Documentation:**
   - README với setup
   - Environment variables
   - API integration guide
   - Component usage examples

3. **Code Quality:**
   - Clean, readable code
   - Proper TypeScript types
   - Comments cho complex logic
   - Consistent code style
   - Error boundaries

---

## NOTES & CONSIDERATIONS

### Backend Requirements (cần bổ sung)

1. **Auto-create group chat thread:**
   - Khi tạo group → Tạo `ChatThread` với `type='group'`, `group_id={groupId}`
   - Khi user join group → Auto add vào `ChatParticipant`
   - Khi user leave group → Auto remove từ `ChatParticipant`

2. **API Endpoint:**
   - `GET /api/chat/threads/group/{groupId}` - Lấy group thread
   - Hoặc filter từ `GET /api/chat/threads` với `group_id`

3. **Posts Count:**
   - Thêm `posts_count` vào Group model hoặc tính từ API

### Performance Optimizations

- Virtual scrolling cho posts list (nếu nhiều posts)
- Image lazy loading
- Optimistic UI updates
- Cache groups list
- Debounce search

### Security

- Verify permissions trước khi hiển thị actions
- Validate user membership trước khi post/chat
- Handle 403 errors gracefully

---

**Hãy tạo một hệ thống Groups hoàn chỉnh, đẹp mắt, và có trải nghiệm người dùng tốt với tất cả các tính năng được mô tả ở trên. Tích hợp seamlessly với hệ thống chat realtime đã có.**

