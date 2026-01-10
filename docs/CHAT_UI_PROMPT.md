# PROMPT: Tạo Giao Diện Chat Realtime với Next.js

## YÊU CẦU TỔNG QUAN

Tạo một ứng dụng chat realtime hoàn chỉnh với Next.js (App Router), TypeScript, Tailwind CSS, và Laravel Echo để kết nối với Laravel Reverb backend. Ứng dụng cần hỗ trợ chat 1-1 (direct messaging), hiển thị realtime, typing indicators, read receipts, reactions, và attachments.

---

## KIẾN TRÚC BACKEND API

### Base URL
- API Base: `http://localhost:8000/api` (hoặc domain production)
- Broadcasting: Laravel Reverb (WebSocket)
- Authentication: Laravel Sanctum (Bearer Token)

### API Endpoints

#### 1. Lấy danh sách threads của user hiện tại
```
GET /api/chat/threads
Headers: Authorization: Bearer {token}
Response: Array<ChatThread>
```

**ChatThread Structure:**
```typescript
interface ChatThread {
  id: number;
  type: 'direct' | 'group';
  name?: string;
  owner_id?: number;
  group_id?: number;
  course_id?: number;
  created_at: string;
  updated_at: string;
  participants: Array<{
    id: number;
    thread_id: number;
    user_id: number;
    last_read_at: string | null;
    created_at: string;
    updated_at: string;
    user: {
      id: number;
      name: string;
    };
  }>;
}
```

#### 2. Tạo hoặc lấy direct thread với user khác
```
POST /api/chat/threads/direct
Headers: Authorization: Bearer {token}
Body: { user_id: number }
Response: ChatThread
```

#### 3. Lấy messages của thread (pagination)
```
GET /api/chat/threads/{threadId}/messages?limit=30&page=1
Headers: Authorization: Bearer {token}
Response: {
  data: Array<ChatMessage>;
  current_page: number;
  last_page: number;
  per_page: number;
  total: number;
}
```

**ChatMessage Structure:**
```typescript
interface ChatMessage {
  id: number;
  thread_id: number;
  user_id: number;
  content: string | null;
  attachments: string[] | null; // Array of file URLs/paths
  created_at: string;
  updated_at: string;
  user: {
    id: number;
    name: string;
  };
  reactions?: Array<{
    id: number;
    user_id: number;
    reaction_type: string; // e.g., 'like', 'love', 'haha', etc.
    user?: {
      id: number;
      name: string;
    };
  }>;
}
```
#### 4. Gửi tin nhắn
```
POST /api/chat/threads/{threadId}/messages
Headers: Authorization: Bearer {token}
Body: {
  content?: string;
  attachments?: string[];
}
Response: ChatMessage
```
#### 5. Đánh dấu đã đọc
```
POST /api/chat/threads/{threadId}/read
Headers: Authorization: Bearer {token}
Response: { message: 'Marked as read' }
```
#### 6. Gửi typing indicator
```
POST /api/chat/threads/{threadId}/typing
Headers: Authorization: Bearer {token}
Response: { typing: true }
```
#### 7. React tin nhắn
```
POST /api/chat/messages/{messageId}/react
Headers: Authorization: Bearer {token}
Body: { reaction_type: string }
Response: { message: 'Reacted' }
```
#### 8. Xóa reaction
```
DELETE /api/chat/messages/{messageId}/react
Headers: Authorization: Bearer {token}
Response: { message: 'Reaction removed' }
```
---
## BROADCASTING EVENTS (Laravel Echo)

### Kết nối
- Sử dụng Laravel Echo với Reverb driver
- Channel: `chat.thread.{threadId}` (private channel)
- Authentication: Gửi token qua Echo config

### Events

#### 1. `message.created`
Khi có tin nhắn mới được gửi trong thread
```typescript
{
  message: ChatMessage; // Full message object với user info
  threadId: number;
}
```
#### 2. `thread.read`
Khi user khác đánh dấu đã đọc thread
```typescript
{
  threadId: number;
  userId: number;
  userName: string;
}
```
#### 3. `user.typing`
Khi user khác đang gõ tin nhắn
```typescript
{
  threadId: number;
  userId: number;
  userName: string;
}
```
---
## YÊU CẦU GIAO DIỆN

### Layout Tổng Quan
- **Layout 2 cột:**
  - Cột trái (30%): Danh sách threads/conversations
  - Cột phải (70%): Chat window với messages và input

### 1. Sidebar - Danh Sách Threads

**Component: `ThreadList`**

**Features:**
- Hiển thị danh sách threads của user (từ API `/api/chat/threads`)
- Mỗi thread item hiển thị:
  - Avatar của người chat (hoặc icon group nếu là group chat)
  - Tên người chat (hoặc tên group)
  - Preview tin nhắn cuối cùng (truncate nếu quá dài)
  - Timestamp tin nhắn cuối (relative time: "2 phút trước", "Hôm qua", etc.)
  - Badge số tin nhắn chưa đọc (nếu có)
  - Indicator thread đang active/selected
- Click vào thread → Load messages của thread đó
- Search/filter threads (optional)
- Auto-refresh khi có thread mới

**UI/UX:**
- Hover effect trên mỗi thread item
- Active state rõ ràng
- Scrollable list
- Loading skeleton khi fetch data
- Empty state khi chưa có thread nào

### 2. Chat Window

**Component: `ChatWindow`**

**Header:**
- Hiển thị tên người chat (hoặc group name)
- Avatar
- Online/offline status (nếu có)
- Menu actions (optional: delete conversation, etc.)

**Messages Area:**
- Scrollable container
- Hiển thị messages theo thứ tự thời gian (oldest → newest)
- Auto-scroll xuống tin nhắn mới nhất khi:
  - Load messages lần đầu
  - Có tin nhắn mới từ realtime event
  - User gửi tin nhắn mới
- **Message Bubble:**
  - Tin nhắn của mình: align right, màu primary
  - Tin nhắn của người khác: align left, màu gray/white
  - Hiển thị:
    - Avatar (chỉ với tin nhắn đầu tiên của user trong chuỗi liên tiếp)
    - Tên người gửi (chỉ với tin nhắn của người khác, nếu là tin nhắn đầu tiên)
    - Nội dung text
    - Attachments (nếu có): hiển thị preview images, download links cho files
    - Timestamp (hiển thị relative time, hoặc absolute nếu quá cũ)
    - Reactions (nếu có): hiển thị emoji/icons bên dưới message
    - Read receipt: "Đã xem" với timestamp (nếu là tin nhắn của mình)
- Group consecutive messages từ cùng một user
- Date separator: Hiển thị "Hôm nay", "Hôm qua", hoặc date khi có thay đổi ngày
- Infinite scroll: Load more messages khi scroll lên trên (pagination)

**Input Area:**
- Text input với placeholder "Nhập tin nhắn..."
- Button attach file (upload images/files)
- Button emoji picker (optional)
- Button send (hoặc Enter để gửi, Shift+Enter để xuống dòng)
- Preview attachments trước khi gửi
- Typing indicator: Hiển thị "{userName} đang gõ..." khi nhận event `user.typing`
- Debounce typing indicator: Chỉ gửi event typing sau 1-2 giây user ngừng gõ

**Reactions:**
- Click vào message → hiển thị reaction picker (emoji: 👍, ❤️, 😂, 😮, 😢, 🙏)
- Hiển thị reactions hiện có bên dưới message
- Click vào reaction để toggle (add/remove)

### 3. Real-time Features

**Kết nối Laravel Echo:**
```typescript
// Setup Echo
import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

const echo = new Echo({
  broadcaster: 'reverb',
  key: process.env.NEXT_PUBLIC_REVERB_APP_KEY,
  wsHost: process.env.NEXT_PUBLIC_REVERB_HOST,
  wsPort: process.env.NEXT_PUBLIC_REVERB_PORT || 443,
  wssPort: process.env.NEXT_PUBLIC_REVERB_PORT || 443,
  forceTLS: true,
  enabledTransports: ['ws', 'wss'],
  authEndpoint: '/api/broadcasting/auth',
  auth: {
    headers: {
      Authorization: `Bearer ${token}`,
    },
  },
});
```
**Subscribe to thread channel:**
```typescript
echo.private(`chat.thread.${threadId}`)
  .listen('.message.created', (e: { message: ChatMessage }) => {
    // Add new message to list
  })
  .listen('.thread.read', (e: { userId: number, userName: string }) => {
    // Update read receipt
  })
  .listen('.user.typing', (e: { userId: number, userName: string }) => {
    // Show typing indicator
  });
```
**Auto-mark as read:**
- Khi user mở thread → gọi API `POST /api/chat/threads/{threadId}/read`
- Khi user scroll xuống cuối và xem tin nhắn mới → auto mark as read

---
## TECHNICAL REQUIREMENTS

### State Management
- Quản lý threads list
- Quản lý messages của thread hiện tại
- Quản lý typing indicators
- Quản lý read receipts
- Quản lý authentication token

### Error Handling
- Handle network errors
- Handle WebSocket disconnection (reconnect logic)
- Show error messages to user
- Retry failed requests

### Performance
- Virtual scrolling cho messages list (nếu có nhiều messages)
- Debounce typing indicator
- Optimistic UI updates (hiển thị tin nhắn ngay khi gửi, update sau khi server confirm)
- Image lazy loading cho attachments

### Responsive Design
- Mobile: Full screen chat, có button back để quay lại thread list
- Tablet: Sidebar có thể collapse
- Desktop: 2-column layout như mô tả

---
## COMPONENT STRUCTURE SUGGESTED

```
components/chat/
├── ThreadList.tsx          # Sidebar với danh sách threads
├── ThreadItem.tsx          # Item trong thread list
├── ChatWindow.tsx          # Main chat window
├── ChatHeader.tsx          # Header của chat window
├── MessagesList.tsx        # Container cho messages
├── MessageBubble.tsx       # Single message bubble
├── MessageInput.tsx        # Input area với text, attach, send
├── TypingIndicator.tsx     # "User is typing..." indicator
├── ReactionPicker.tsx      # Emoji picker cho reactions
└── AttachmentPreview.tsx   # Preview attachments

hooks/
├── useChat.ts              # Main chat hook với Echo logic
├── useThreads.ts           # Fetch và manage threads
├── useMessages.ts          # Fetch và manage messages
└── useTyping.ts            # Typing indicator logic

utils/
├── api.ts                  # API client với axios
├── echo.ts                 # Echo instance setup
└── dateFormat.ts           # Date formatting utilities
```
---

## UI/UX GUIDELINES

### Colors
- Primary color cho tin nhắn của mình
- Neutral gray cho tin nhắn của người khác
- Accent color cho active states, buttons
- Success color cho read receipts
- Error color cho error states

### Spacing
- Consistent padding/margin giữa messages
- Adequate spacing trong input area
- Comfortable touch targets (min 44x44px trên mobile)

### Animations
- Smooth scroll to bottom
- Fade-in cho messages mới
- Typing indicator animation (dots bouncing)
- Button hover/active states
---

## TESTING SCENARIOS
1. **Load threads list** → Verify hiển thị đúng threads
2. **Select thread** → Verify load messages và subscribe channel
3. **Send message** → Verify hiển thị ngay (optimistic), verify realtime event
4. **Receive message** → Verify hiển thị từ realtime event
5. **Typing indicator** → Verify hiển thị khi user khác đang gõ
6. **Read receipt** → Verify cập nhật khi user khác đánh dấu đã đọc
7. **Reactions** → Verify add/remove reaction
8. **Attachments** → Verify upload và hiển thị
9. **Pagination** → Verify load more messages khi scroll lên
10. **Reconnection** → Verify reconnect khi WebSocket disconnect

---

## DELIVERABLES

1. **Complete Next.js application** với:
   - TypeScript types cho tất cả API responses
   - All components được mô tả
   - Hooks và utilities
   - Error handling và loading states
   - Responsive design

2. **Documentation**:
   - README với setup instructions
   - Environment variables cần thiết
   - API integration guide

3. **Code quality**:
   - Clean, readable code
   - Proper TypeScript types
   - Comments cho complex logic
   - Consistent code style

---

## TÀI LIỆU BỔ SUNG

### TypeScript Types
File `chat-type.ts` đã được tạo sẵn với đầy đủ types cho:
- API responses và requests
- Broadcasting events
- UI state types
- Component props types
- Hook return types

### API client setup hoàn chỉnh
Hiện tại trong dự án đã có:
- Laravel Echo configuration
- Custom hooks (useChat, useTyping)
- Date formatting utilities
- File upload utilities
- Reaction emoji mapping

---

**Hãy tạo một chức năng chat realtime hoàn chỉnh, đẹp mắt, và có trải nghiệm người dùng tốt với tất cả các tính năng được mô tả ở trên. Sử dụng các types và examples đã được cung cấp để đảm bảo tính nhất quán và type safety.**