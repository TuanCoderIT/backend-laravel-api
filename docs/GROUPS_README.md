# Groups Feature - Frontend Implementation Documentation

Tài liệu đầy đủ để triển khai tính năng Groups (Nhóm Học Tập) trên frontend Next.js 15, tích hợp với backend Laravel API.

---

## 📚 TÀI LIỆU

### 1. [GROUPS_UI_PROMPT.md](./GROUPS_UI_PROMPT.md)
**Prompt chi tiết cho AI/Developer** - Mô tả đầy đủ yêu cầu UI/UX, components, features, và technical requirements.

**Nội dung:**
- Yêu cầu tổng quan
- API endpoints chi tiết
- Tích hợp Group Chat
- Yêu cầu giao diện (Groups Listing, Group Detail, Chat Integration)
- Technical requirements
- Component structure
- UI/UX guidelines
- Testing scenarios

**Sử dụng khi:** Cần prompt để tạo frontend hoàn chỉnh.

---

### 2. [GROUPS_IMPLEMENTATION_GUIDE.md](./GROUPS_IMPLEMENTATION_GUIDE.md)
**Hướng dẫn triển khai chi tiết** - Code examples, hooks, components, và best practices.

**Nội dung:**
- Cấu trúc project
- Setup dependencies
- TypeScript types
- API client setup
- Custom hooks (useGroups, useGroupDetail, useGroupPosts, etc.)
- Components examples
- Pages & routes
- Tích hợp chat
- Testing & troubleshooting

**Sử dụng khi:** Đang implement code, cần examples và patterns.

---

### 3. [GROUPS_API_REFERENCE.md](./GROUPS_API_REFERENCE.md)
**Tham khảo nhanh API** - Tất cả endpoints với request/response examples.

**Nội dung:**
- Tất cả API endpoints
- Request/Response examples
- Error responses
- Realtime events
- Usage examples
- Backend requirements notes

**Sử dụng khi:** Cần tra cứu nhanh API endpoints.

---

## 🎯 TỔNG QUAN TÍNH NĂNG

### 1. Groups Listing (`/groups`)
- Hiển thị danh sách tất cả nhóm
- Search, filter, sort
- Join/Leave từ listing
- Responsive grid layout

### 2. Group Detail (`/groups/[slug]`)
- Header với cover image, info, actions
- 3 Tabs:
  - **Posts**: Feed bài viết của nhóm
  - **Members**: Danh sách thành viên + quản lý
  - **Chat**: Chat nhóm realtime
- Join/Leave functionality
- Quản lý nhóm (owner/admin)

### 3. Group Chat Integration
- Mỗi group có 1 chat thread
- Auto-join khi join group
- Hiển thị trong sidebar chat
- Realtime messaging
- Typing indicators
- Unread count

---

## 🚀 QUICK START

### Bước 1: Đọc tài liệu
1. Đọc `GROUPS_UI_PROMPT.md` để hiểu requirements
2. Đọc `GROUPS_IMPLEMENTATION_GUIDE.md` để xem code examples
3. Tham khảo `GROUPS_API_REFERENCE.md` khi cần

### Bước 2: Setup Project
```bash
# Install dependencies
npm install axios swr date-fns react-hook-form zod

# Setup environment variables
NEXT_PUBLIC_API_URL=http://localhost:8000/api
NEXT_PUBLIC_REVERB_APP_KEY=your-key
NEXT_PUBLIC_REVERB_HOST=localhost
NEXT_PUBLIC_REVERB_PORT=8080
```

### Bước 3: Tạo Types
Copy types từ `GROUPS_IMPLEMENTATION_GUIDE.md` → `types/group.ts`

### Bước 4: Setup API Client
Copy API client từ `GROUPS_IMPLEMENTATION_GUIDE.md` → `lib/api.ts`

### Bước 5: Tạo Hooks
Tạo các hooks:
- `hooks/useGroups.ts`
- `hooks/useGroupDetail.ts`
- `hooks/useGroupPosts.ts`
- `hooks/useGroupMembers.ts`
- `hooks/useGroupChat.ts`

### Bước 6: Tạo Components
Tạo components theo structure trong `GROUPS_UI_PROMPT.md`

### Bước 7: Tạo Pages
- `app/groups/page.tsx` - Listing
- `app/groups/[slug]/page.tsx` - Detail

---

## 📋 CHECKLIST TRIỂN KHAI

### Phase 1: Core Features
- [ ] Groups Listing page
- [ ] Group Detail page
- [ ] Join/Leave functionality
- [ ] Group Posts feed
- [ ] Group Members list

### Phase 2: Management
- [ ] Create/Edit/Delete group (owner)
- [ ] Member management (kick, promote, demote)
- [ ] Join requests (private groups)
- [ ] Group settings

### Phase 3: Chat Integration
- [ ] Group chat thread
- [ ] Chat UI trong Group Detail
- [ ] Sidebar integration
- [ ] Unread count
- [ ] Realtime updates

### Phase 4: Polish
- [ ] Search & filter
- [ ] Pagination/infinite scroll
- [ ] Loading states
- [ ] Error handling
- [ ] Responsive design
- [ ] Animations

---

## 🔧 BACKEND REQUIREMENTS

### Cần bổ sung trong Backend:

1. **Auto-create Group Chat Thread**
   ```php
   // Khi tạo group
   $thread = ChatThread::create([
       'type' => 'group',
       'name' => $group->name,
       'group_id' => $group->id,
   ]);
   
   // Thêm owner vào participants
   ChatParticipant::create([
       'thread_id' => $thread->id,
       'user_id' => $group->owner_id,
   ]);
   ```

2. **Auto-join Chat khi Join Group**
   ```php
   // Trong GroupMemberController@join
   $thread = ChatThread::where('group_id', $groupId)
       ->where('type', 'group')
       ->first();
   
   if ($thread) {
       ChatParticipant::firstOrCreate([
           'thread_id' => $thread->id,
           'user_id' => Auth::id(),
       ]);
   }
   ```

3. **Auto-leave Chat khi Leave Group**
   ```php
   // Trong GroupMemberController@leave
   $thread = ChatThread::where('group_id', $groupId)
       ->where('type', 'group')
       ->first();
   
   if ($thread) {
       ChatParticipant::where('thread_id', $thread->id)
           ->where('user_id', Auth::id())
           ->delete();
   }
   ```

4. **Optional: Endpoint riêng cho Group Thread**
   ```php
   // routes/api.php
   Route::get('/chat/threads/group/{groupId}', [ChatController::class, 'getGroupThread']);
   ```

5. **Thêm posts_count vào Group**
   ```php
   // Migration hoặc tính từ API
   $group->withCount('posts');
   ```

---

## 🎨 UI/UX GUIDELINES

### Colors
- Primary: Buttons, links
- Success: Join, approved
- Warning: Pending
- Danger: Leave, kick
- Neutral: Cards, backgrounds

### Layout
- Mobile: 1 column
- Tablet: 2 columns
- Desktop: 3-4 columns

### Components
- Consistent spacing
- Loading skeletons
- Empty states
- Error states
- Smooth animations

---

## 🧪 TESTING

### Test Scenarios

1. **Groups Listing**
   - Load list
   - Search
   - Filter
   - Join/Leave

2. **Group Detail**
   - Load info
   - Switch tabs
   - Join/Leave
   - View posts/members/chat

3. **Group Chat**
   - Load thread
   - Send/receive
   - Typing indicators
   - Unread count

4. **Permissions**
   - Owner actions
   - Admin actions
   - Member actions
   - Non-member restrictions

---

## 📝 NOTES

### Tích hợp với Chat System hiện có
- Reuse components từ chat system
- Group threads hiển thị trong sidebar
- Same realtime events
- Same message format

### Performance
- Virtual scrolling cho long lists
- Image lazy loading
- Optimistic UI updates
- Debounce search

### Security
- Verify permissions trước khi hiển thị actions
- Validate membership trước khi post/chat
- Handle 403 errors gracefully

---

## 🔗 LIÊN KẾT

- [CHAT_UI_PROMPT.md](./CHAT_UI_PROMPT.md) - Chat system documentation
- [CHAT_IMPLEMENTATION_GUIDE.md](./CHAT_IMPLEMENTATION_GUIDE.md) - Chat implementation
- [chat-type.ts](./chat-type.ts) - TypeScript types cho chat

---

## 💡 TIPS

1. **Bắt đầu từ Listing page** - Đơn giản nhất, dễ test
2. **Sau đó làm Detail page** - Core functionality
3. **Cuối cùng tích hợp Chat** - Phức tạp nhất
4. **Test từng feature** - Đừng làm tất cả cùng lúc
5. **Reuse components** - Từ chat system, posts system

---

## ❓ FAQ

**Q: Group chat thread không tồn tại?**
A: Backend cần auto-create khi tạo group. Xem Backend Requirements.

**Q: Unread count không chính xác?**
A: Tính từ `last_read_at` trong `ChatParticipant`. Xem `useGroupChat` hook.

**Q: Performance issues với nhiều groups?**
A: Implement pagination, virtual scrolling, lazy loading.

**Q: Làm sao test realtime?**
A: Mở 2 tabs/browsers, join cùng group, test chat.

---

## 📞 SUPPORT

Nếu gặp vấn đề:
1. Kiểm tra API endpoints trong `GROUPS_API_REFERENCE.md`
2. Xem code examples trong `GROUPS_IMPLEMENTATION_GUIDE.md`
3. Verify backend requirements
4. Check console logs
5. Review error responses

---

**Tài liệu này cung cấp hướng dẫn đầy đủ để triển khai tính năng Groups. Hãy bắt đầu với `GROUPS_UI_PROMPT.md` để hiểu requirements, sau đó sử dụng `GROUPS_IMPLEMENTATION_GUIDE.md` để implement code.**

