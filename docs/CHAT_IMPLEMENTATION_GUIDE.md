# Hướng Dẫn Triển Khai Chat Realtime Frontend

Tài liệu này hướng dẫn cách sử dụng các file đã được tạo để implement chat realtime frontend với Next.js.

## Cấu Trúc Files

```
backend-laravel-api/
├── CHAT_UI_PROMPT.md              # Prompt chi tiết cho AI
├── chat-type.ts                    # TypeScript types
├── FRONTEND_EXAMPLES.md            # Code examples
└── CHAT_IMPLEMENTATION_GUIDE.md    # File này
```

## Bước 1: Copy Types vào Next.js Project

1. Copy file `chat-type.ts` vào thư mục `types/` hoặc `lib/types/` trong dự án Next.js
2. Đảm bảo import path đúng trong các file khác

```typescript
// Ví dụ: types/chat-type.ts
import type { ChatThread, ChatMessage } from '@/types/chat-type';
```

## Bước 2: Setup Dependencies

Cài đặt các packages cần thiết:

```bash
pnpm add laravel-echo pusher-js date-fns react-hook-form swr

pnpm add -D autoprefixer postcss
```

## Bước 3: Cấu Hình Environment Variables

Tạo file `.env.local` trong Next.js project:

```env
NEXT_PUBLIC_API_URL=http://localhost:8000/api
NEXT_PUBLIC_REVERB_APP_KEY=your-reverb-app-key
NEXT_PUBLIC_REVERB_HOST=localhost
NEXT_PUBLIC_REVERB_PORT=8080
NEXT_PUBLIC_REVERB_SCHEME=http
```

**Lưu ý**: Lấy các giá trị này từ file `.env` của Laravel backend.

## Bước 4: Copy Utilities từ FRONTEND_EXAMPLES.md

Copy các file sau vào dự án Next.js:

1. **`lib/api.ts`** - API client
2. **`lib/echo.ts`** - Laravel Echo setup
3. **`lib/dateFormat.ts`** - Date formatting utilities
4. **`lib/reactions.ts`** - Reaction emoji mapping
5. **`lib/upload.ts`** - File upload utility (cần customize)
6. **`hooks/useChat.ts`** - Main chat hook
7. **`hooks/useTyping.ts`** - Typing indicator hook

## Bước 5: Tạo Components

Sử dụng prompt trong `CHAT_UI_PROMPT.md` để tạo các components:

### Components cần tạo:

1. **`components/chat/ThreadList.tsx`**
   - Hiển thị danh sách threads
   - Props: `threads`, `currentThreadId`, `onSelectThread`, `unreadCounts`

2. **`components/chat/ThreadItem.tsx`**
   - Item trong thread list
   - Props: `thread`, `isActive`, `unreadCount`, `onClick`, `currentUserId`

3. **`components/chat/ChatWindow.tsx`**
   - Main chat window container
   - Props: `thread`, `messages`, `currentUserId`, `onSendMessage`, etc.

4. **`components/chat/ChatHeader.tsx`**
   - Header của chat window
   - Hiển thị tên, avatar, status

5. **`components/chat/MessagesList.tsx`**
   - Container cho messages với infinite scroll
   - Props: `messages`, `currentUserId`, `onLoadMore`, `hasMore`

6. **`components/chat/MessageBubble.tsx`**
   - Single message bubble
   - Props: `message`, `isOwn`, `showAvatar`, `showName`, `onReact`, `currentUserId`

7. **`components/chat/MessageInput.tsx`**
   - Input area với text, attach, send
   - Props: `onSend`, `onTyping`, `disabled`

8. **`components/chat/TypingIndicator.tsx`**
   - Hiển thị "User is typing..."
   - Props: `typingUsers`

9. **`components/chat/ReactionPicker.tsx`**
   - Emoji picker cho reactions
   - Props: `onSelect`, `onClose`

10. **`components/chat/AttachmentPreview.tsx`**
    - Preview attachments trước khi gửi
    - Props: `attachments`, `onRemove`

## Bước 6: Tạo Main Chat Page

Tạo page `app/chat/page.tsx` hoặc `pages/chat.tsx`:

```typescript
'use client';

import { useChat } from '@/hooks/useChat';
import { ThreadList } from '@/components/chat/ThreadList';
import { ChatWindow } from '@/components/chat/ChatWindow';
import { useState, useEffect } from 'react';

export default function ChatPage() {
  // Lấy currentUserId từ auth context/store
  const currentUserId = 1; // TODO: Get from auth
  
  const {
    threads,
    currentThread,
    messages,
    isLoading,
    isSending,
    typingUsers,
    unreadCounts,
    selectThread,
    sendMessage,
    markAsRead,
    reactToMessage,
    removeReaction,
    loadMoreMessages,
    hasMoreMessages,
  } = useChat(currentUserId);

  if (isLoading) {
    return <div>Loading...</div>;
  }

  return (
    <div className="flex h-screen">
      <div className="w-1/3 border-r">
        <ThreadList
          threads={threads}
          currentThreadId={currentThread?.id || null}
          onSelectThread={selectThread}
          unreadCounts={unreadCounts}
        />
      </div>
      <div className="flex-1">
        {currentThread ? (
          <ChatWindow
            thread={currentThread}
            messages={messages}
            currentUserId={currentUserId}
            onSendMessage={sendMessage}
            onMarkAsRead={markAsRead}
            onReact={reactToMessage}
            onRemoveReaction={removeReaction}
            typingUsers={typingUsers}
          />
        ) : (
          <div className="flex items-center justify-center h-full">
            <p>Chọn một cuộc trò chuyện để bắt đầu</p>
          </div>
        )}
      </div>
    </div>
  );
}
```

## Bước 7: Setup Tailwind CSS

Đảm bảo Tailwind CSS đã được cấu hình:

```javascript
// tailwind.config.js
module.exports = {
  content: [
    './pages/**/*.{js,ts,jsx,tsx,mdx}',
    './components/**/*.{js,ts,jsx,tsx,mdx}',
    './app/**/*.{js,ts,jsx,tsx,mdx}',
  ],
  theme: {
    extend: {
      // Customize colors, spacing, etc.
    },
  },
  plugins: [],
}
```

## Bước 8: Testing

### Test các tính năng:

1. **Load threads**: Verify danh sách threads hiển thị đúng
2. **Select thread**: Verify messages được load và channel được subscribe
3. **Send message**: 
   - Gửi tin nhắn và verify hiển thị ngay (optimistic update)
   - Verify realtime event được nhận
4. **Receive message**: Mở 2 tab, gửi từ tab 1, verify tab 2 nhận được
5. **Typing indicator**: Gõ trong tab 1, verify tab 2 hiển thị "User is typing..."
6. **Read receipt**: Đánh dấu đã đọc, verify cập nhật
7. **Reactions**: Add/remove reaction, verify cập nhật
8. **Pagination**: Scroll lên trên, verify load more messages

## Bước 9: Customization

### Colors & Styling
- Customize Tailwind config cho brand colors
- Update component styles theo design system

### Features
- Thêm search/filter threads
- Thêm delete conversation
- Thêm edit message
- Thêm reply to message
- Thêm forward message
- Thêm voice messages
- Thêm video calls (nếu cần)

### Performance
- Implement virtual scrolling cho messages list
- Add image lazy loading
- Optimize bundle size với code splitting

## Troubleshooting

### WebSocket không kết nối được
- Kiểm tra Reverb server đang chạy
- Verify environment variables
- Check CORS settings
- Verify authentication token

### Messages không hiển thị realtime
- Check Echo connection status
- Verify channel subscription
- Check event names (phải có dấu `.` trước event name)
- Verify broadcasting auth endpoint

### API calls fail
- Check API base URL
- Verify authentication token
- Check network tab trong DevTools
- Verify Laravel backend đang chạy

## Tài Liệu Tham Khảo

- [Laravel Echo Documentation](https://laravel.com/docs/broadcasting#client-side-installation)
- [Laravel Reverb Documentation](https://reverb.laravel.com/docs)
- [Next.js Documentation](https://nextjs.org/docs)
- [Tailwind CSS Documentation](https://tailwindcss.com/docs)

## Support

Nếu gặp vấn đề, hãy:
1. Kiểm tra console logs
2. Verify backend API hoạt động đúng
3. Check WebSocket connection
4. Review code examples trong `FRONTEND_EXAMPLES.md`

