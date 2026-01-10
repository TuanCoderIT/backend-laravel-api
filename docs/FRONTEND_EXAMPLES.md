# Frontend Implementation Examples

## 1. API Client Setup

### `lib/api.ts`
```typescript
import axios, { AxiosInstance, AxiosError } from 'axios';
import type { 
  ChatThread, 
  ChatMessage, 
  PaginatedResponse,
  CreateDirectThreadRequest,
  SendMessageRequest,
  ReactMessageRequest,
  MessagesQueryParams
} from '@/types/chat-type';

class ApiClient {
  private client: AxiosInstance;

  constructor() {
    this.client = axios.create({
      baseURL: process.env.NEXT_PUBLIC_API_URL || 'http://localhost:8000/api',
      headers: {
        'Content-Type': 'application/json',
      },
    });

    // Interceptor để thêm token vào mọi request
    this.client.interceptors.request.use((config) => {
      const token = this.getToken();
      if (token) {
        config.headers.Authorization = `Bearer ${token}`;
      }
      return config;
    });

    // Interceptor để handle errors
    this.client.interceptors.response.use(
      (response) => response,
      (error: AxiosError) => {
        if (error.response?.status === 401) {
          // Handle unauthorized - redirect to login
          this.clearToken();
          if (typeof window !== 'undefined') {
            window.location.href = '/login';
          }
        }
        return Promise.reject(error);
      }
    );
  }

  private getToken(): string | null {
    if (typeof window === 'undefined') return null;
    return localStorage.getItem('auth_token');
  }

  private clearToken(): void {
    if (typeof window !== 'undefined') {
      localStorage.removeItem('auth_token');
    }
  }

  // Chat API Methods
  async getThreads(): Promise<ChatThread[]> {
    const { data } = await this.client.get<ChatThread[]>('/chat/threads');
    return data;
  }

  async createDirectThread(userId: number): Promise<ChatThread> {
    const { data } = await this.client.post<ChatThread>(
      '/chat/threads/direct',
      { user_id: userId } as CreateDirectThreadRequest
    );
    return data;
  }

  async getMessages(
    threadId: number,
    params?: MessagesQueryParams
  ): Promise<PaginatedResponse<ChatMessage>> {
    const { data } = await this.client.get<PaginatedResponse<ChatMessage>>(
      `/chat/threads/${threadId}/messages`,
      { params }
    );
    return data;
  }

  async sendMessage(
    threadId: number,
    payload: SendMessageRequest
  ): Promise<ChatMessage> {
    const { data } = await this.client.post<ChatMessage>(
      `/chat/threads/${threadId}/messages`,
      payload
    );
    return data;
  }

  async markAsRead(threadId: number): Promise<void> {
    await this.client.post(`/chat/threads/${threadId}/read`);
  }

  async sendTyping(threadId: number): Promise<void> {
    await this.client.post(`/chat/threads/${threadId}/typing`);
  }

  async reactToMessage(
    messageId: number,
    reactionType: string
  ): Promise<void> {
    await this.client.post(`/chat/messages/${messageId}/react`, {
      reaction_type: reactionType,
    } as ReactMessageRequest);
  }

  async removeReaction(messageId: number): Promise<void> {
    await this.client.delete(`/chat/messages/${messageId}/react`);
  }
}

export const apiClient = new ApiClient();
```

## 2. Laravel Echo Setup

### `lib/echo.ts`
```typescript
import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

let echoInstance: Echo | null = null;

export const getEcho = (): Echo => {
  if (echoInstance) {
    return echoInstance;
  }

  if (typeof window === 'undefined') {
    throw new Error('Echo can only be initialized on client side');
  }

  // Get token from storage
  const token = localStorage.getItem('auth_token');

  echoInstance = new Echo({
    broadcaster: 'reverb',
    key: process.env.NEXT_PUBLIC_REVERB_APP_KEY!,
    wsHost: process.env.NEXT_PUBLIC_REVERB_HOST || 'localhost',
    wsPort: parseInt(process.env.NEXT_PUBLIC_REVERB_PORT || '8080'),
    wssPort: parseInt(process.env.NEXT_PUBLIC_REVERB_PORT || '8080'),
    forceTLS: process.env.NEXT_PUBLIC_REVERB_SCHEME === 'https',
    enabledTransports: ['ws', 'wss'],
    authEndpoint: `${process.env.NEXT_PUBLIC_API_URL}/broadcasting/auth`,
    auth: {
      headers: {
        Authorization: `Bearer ${token}`,
      },
    },
  });

  return echoInstance;
};

export const disconnectEcho = (): void => {
  if (echoInstance) {
    echoInstance.disconnect();
    echoInstance = null;
  }
};
```

## 3. Date Formatting Utilities

### `lib/dateFormat.ts`
```typescript
import { format, formatDistanceToNow, isToday, isYesterday, parseISO } from 'date-fns';
import { vi } from 'date-fns/locale';

export const formatMessageTime = (dateString: string): string => {
  const date = parseISO(dateString);
  
  if (isToday(date)) {
    return format(date, 'HH:mm');
  }
  
  if (isYesterday(date)) {
    return 'Hôm qua';
  }
  
  // Nếu trong cùng tuần
  const daysDiff = Math.floor(
    (Date.now() - date.getTime()) / (1000 * 60 * 60 * 24)
  );
  
  if (daysDiff < 7) {
    return format(date, 'EEEE', { locale: vi }); // Thứ Hai, Thứ Ba, etc.
  }
  
  // Nếu quá 7 ngày, hiển thị ngày tháng
  return format(date, 'dd/MM/yyyy');
};

export const formatRelativeTime = (dateString: string): string => {
  const date = parseISO(dateString);
  return formatDistanceToNow(date, {
    addSuffix: true,
    locale: vi,
  });
};

export const formatDateSeparator = (dateString: string): string => {
  const date = parseISO(dateString);
  
  if (isToday(date)) {
    return 'Hôm nay';
  }
  
  if (isYesterday(date)) {
    return 'Hôm qua';
  }
  
  return format(date, 'dd MMMM yyyy', { locale: vi });
};

export const isSameDay = (date1: string, date2: string): boolean => {
  const d1 = parseISO(date1);
  const d2 = parseISO(date2);
  return (
    d1.getDate() === d2.getDate() &&
    d1.getMonth() === d2.getMonth() &&
    d1.getFullYear() === d2.getFullYear()
  );
};
```

## 4. Custom Hook: useChat

### `hooks/useChat.ts`
```typescript
import { useState, useEffect, useCallback, useRef } from 'react';
import { getEcho } from '@/lib/echo';
import { apiClient } from '@/lib/api';
import type {
  ChatThread,
  ChatMessage,
  TypingUser,
  UseChatReturn,
} from '@/types/chat-type';

export const useChat = (currentUserId: number): UseChatReturn => {
  const [threads, setThreads] = useState<ChatThread[]>([]);
  const [currentThreadId, setCurrentThreadId] = useState<number | null>(null);
  const [messages, setMessages] = useState<Record<number, ChatMessage[]>>({});
  const [typingUsers, setTypingUsers] = useState<Record<number, TypingUser[]>>({});
  const [unreadCounts, setUnreadCounts] = useState<Record<number, number>>({});
  const [isLoading, setIsLoading] = useState(true);
  const [isSending, setIsSending] = useState(false);
  const [hasMoreMessages, setHasMoreMessages] = useState<Record<number, boolean>>({});
  const [currentPage, setCurrentPage] = useState<Record<number, number>>({});
  
  const echoRef = useRef<any>(null);
  const typingTimeoutRef = useRef<Record<number, NodeJS.Timeout>>({});

  // Load threads
  const loadThreads = useCallback(async () => {
    try {
      const data = await apiClient.getThreads();
      setThreads(data);
    } catch (error) {
      console.error('Failed to load threads:', error);
    } finally {
      setIsLoading(false);
    }
  }, []);

  // Load messages for a thread
  const loadMessages = useCallback(async (threadId: number, page: number = 1) => {
    try {
      const response = await apiClient.getMessages(threadId, { limit: 30, page });
      const existingMessages = messages[threadId] || [];
      
      if (page === 1) {
        setMessages((prev) => ({
          ...prev,
          [threadId]: response.data.reverse(), // Reverse để oldest first
        }));
      } else {
        setMessages((prev) => ({
          ...prev,
          [threadId]: [...response.data.reverse(), ...existingMessages],
        }));
      }
      
      setHasMoreMessages((prev) => ({
        ...prev,
        [threadId]: response.current_page < response.last_page,
      }));
      
      setCurrentPage((prev) => ({
        ...prev,
        [threadId]: response.current_page,
      }));
    } catch (error) {
      console.error('Failed to load messages:', error);
    }
  }, [messages]);

  // Select thread
  const selectThread = useCallback(async (threadId: number) => {
    setCurrentThreadId(threadId);
    
    // Load messages if not loaded
    if (!messages[threadId]) {
      await loadMessages(threadId, 1);
    }
    
    // Mark as read
    try {
      await apiClient.markAsRead(threadId);
      setUnreadCounts((prev) => ({ ...prev, [threadId]: 0 }));
    } catch (error) {
      console.error('Failed to mark as read:', error);
    }
    
    // Subscribe to channel
    if (echoRef.current) {
      const channel = echoRef.current.private(`chat.thread.${threadId}`);
      
      // Listen for new messages
      channel.listen('.message.created', (e: { message: ChatMessage }) => {
        setMessages((prev) => {
          const existing = prev[threadId] || [];
          return {
            ...prev,
            [threadId]: [...existing, e.message],
          };
        });
        
        // Auto mark as read if thread is active
        if (currentThreadId === threadId) {
          apiClient.markAsRead(threadId);
        }
      });
      
      // Listen for read receipts
      channel.listen('.thread.read', (e: { userId: number; userName: string }) => {
        // Update read receipts in messages
        // Implementation depends on your read receipt logic
      });
      
      // Listen for typing
      channel.listen('.user.typing', (e: { userId: number; userName: string }) => {
        if (e.userId === currentUserId) return;
        
        setTypingUsers((prev) => {
          const existing = prev[threadId] || [];
          const filtered = existing.filter((u) => u.userId !== e.userId);
          return {
            ...prev,
            [threadId]: [
              ...filtered,
              { userId: e.userId, userName: e.userName, timestamp: Date.now() },
            ],
          };
        });
        
        // Auto remove typing indicator after 3 seconds
        if (typingTimeoutRef.current[threadId]) {
          clearTimeout(typingTimeoutRef.current[threadId]);
        }
        
        typingTimeoutRef.current[threadId] = setTimeout(() => {
          setTypingUsers((prev) => ({
            ...prev,
            [threadId]: (prev[threadId] || []).filter((u) => u.userId !== e.userId),
          }));
        }, 3000);
      });
    }
  }, [currentThreadId, messages, loadMessages, currentUserId]);

  // Send message
  const sendMessage = useCallback(
    async (content: string, attachments?: string[]) => {
      if (!currentThreadId) return;
      
      setIsSending(true);
      try {
        const newMessage = await apiClient.sendMessage(currentThreadId, {
          content,
          attachments,
        });
        
        // Optimistic update
        setMessages((prev) => {
          const existing = prev[currentThreadId] || [];
          return {
            ...prev,
            [currentThreadId]: [...existing, newMessage],
          };
        });
      } catch (error) {
        console.error('Failed to send message:', error);
        throw error;
      } finally {
        setIsSending(false);
      }
    },
    [currentThreadId]
  );

  // Mark as read
  const markAsRead = useCallback(async () => {
    if (!currentThreadId) return;
    try {
      await apiClient.markAsRead(currentThreadId);
      setUnreadCounts((prev) => ({ ...prev, [currentThreadId]: 0 }));
    } catch (error) {
      console.error('Failed to mark as read:', error);
    }
  }, [currentThreadId]);

  // React to message
  const reactToMessage = useCallback(
    async (messageId: number, reactionType: string) => {
      try {
        await apiClient.reactToMessage(messageId, reactionType);
        // Update local state
        setMessages((prev) => {
          const updated = { ...prev };
          Object.keys(updated).forEach((threadId) => {
            updated[Number(threadId)] = updated[Number(threadId)].map((msg) => {
              if (msg.id === messageId) {
                // Add or update reaction
                const existingReactions = msg.reactions || [];
                const userReaction = existingReactions.find(
                  (r) => r.user_id === currentUserId
                );
                
                if (userReaction) {
                  // Update existing
                  return {
                    ...msg,
                    reactions: existingReactions.map((r) =>
                      r.user_id === currentUserId
                        ? { ...r, reaction_type: reactionType }
                        : r
                    ),
                  };
                } else {
                  // Add new
                  return {
                    ...msg,
                    reactions: [
                      ...existingReactions,
                      {
                        id: Date.now(), // Temporary ID
                        user_id: currentUserId,
                        reaction_type: reactionType,
                      },
                    ],
                  };
                }
              }
              return msg;
            });
          });
          return updated;
        });
      } catch (error) {
        console.error('Failed to react to message:', error);
      }
    },
    [currentUserId]
  );

  // Remove reaction
  const removeReaction = useCallback(async (messageId: number) => {
    try {
      await apiClient.removeReaction(messageId);
      // Update local state
      setMessages((prev) => {
        const updated = { ...prev };
        Object.keys(updated).forEach((threadId) => {
          updated[Number(threadId)] = updated[Number(threadId)].map((msg) => {
            if (msg.id === messageId) {
              return {
                ...msg,
                reactions: (msg.reactions || []).filter(
                  (r) => r.user_id !== currentUserId
                ),
              };
            }
            return msg;
          });
        });
        return updated;
      });
    } catch (error) {
      console.error('Failed to remove reaction:', error);
    }
  }, [currentUserId]);

  // Load more messages
  const loadMoreMessages = useCallback(async () => {
    if (!currentThreadId || !hasMoreMessages[currentThreadId]) return;
    
    const nextPage = (currentPage[currentThreadId] || 1) + 1;
    await loadMessages(currentThreadId, nextPage);
  }, [currentThreadId, hasMoreMessages, currentPage, loadMessages]);

  // Initialize Echo
  useEffect(() => {
    if (typeof window !== 'undefined') {
      try {
        echoRef.current = getEcho();
      } catch (error) {
        console.error('Failed to initialize Echo:', error);
      }
    }
    
    return () => {
      // Cleanup typing timeouts
      Object.values(typingTimeoutRef.current).forEach((timeout) => {
        clearTimeout(timeout);
      });
    };
  }, []);

  // Load threads on mount
  useEffect(() => {
    loadThreads();
  }, [loadThreads]);

  const currentThread = threads.find((t) => t.id === currentThreadId) || null;
  const currentMessages = currentThreadId ? messages[currentThreadId] || [] : [];
  const currentTypingUsers = currentThreadId ? typingUsers[currentThreadId] || [] : [];

  return {
    threads,
    currentThread,
    messages: currentMessages,
    isLoading,
    isSending,
    typingUsers: currentTypingUsers,
    unreadCounts,
    selectThread,
    sendMessage,
    markAsRead,
    reactToMessage,
    removeReaction,
    loadMoreMessages,
    hasMoreMessages: currentThreadId ? hasMoreMessages[currentThreadId] || false : false,
  };
};
```

## 5. Typing Indicator Hook

### `hooks/useTyping.ts`
```typescript
import { useCallback, useRef } from 'react';
import { apiClient } from '@/lib/api';

export const useTyping = (threadId: number | null) => {
  const typingTimeoutRef = useRef<NodeJS.Timeout | null>(null);
  const lastTypingTimeRef = useRef<number>(0);

  const handleTyping = useCallback(() => {
    if (!threadId) return;

    const now = Date.now();
    // Chỉ gửi typing event mỗi 2 giây
    if (now - lastTypingTimeRef.current < 2000) {
      return;
    }

    lastTypingTimeRef.current = now;
    apiClient.sendTyping(threadId).catch(console.error);

    // Clear existing timeout
    if (typingTimeoutRef.current) {
      clearTimeout(typingTimeoutRef.current);
    }
  }, [threadId]);

  return { handleTyping };
};
```

## 6. File Upload Utility

### `lib/upload.ts`
```typescript
export const uploadFile = async (file: File): Promise<string> => {
  // Implement your file upload logic here
  // This is a placeholder - you'll need to integrate with your backend
  // or a service like Cloudinary, S3, etc.
  
  const formData = new FormData();
  formData.append('file', file);
  
  const response = await fetch(`${process.env.NEXT_PUBLIC_API_URL}/upload`, {
    method: 'POST',
    headers: {
      Authorization: `Bearer ${localStorage.getItem('auth_token')}`,
    },
    body: formData,
  });
  
  if (!response.ok) {
    throw new Error('Upload failed');
  }
  
  const data = await response.json();
  return data.url; // Return the file URL
};

export const validateFile = (file: File, maxSize: number = 10 * 1024 * 1024): boolean => {
  if (file.size > maxSize) {
    throw new Error('File size exceeds maximum allowed size');
  }
  
  const allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp', 'application/pdf'];
  if (!allowedTypes.includes(file.type)) {
    throw new Error('File type not allowed');
  }
  
  return true;
};
```

## 7. Reaction Emoji Map

### `lib/reactions.ts`
```typescript
import type { ReactionType } from '@/types/chat-type';

export const REACTION_EMOJIS: Record<ReactionType, string> = {
  like: '👍',
  love: '❤️',
  haha: '😂',
  wow: '😮',
  sad: '😢',
  angry: '😠',
  thanks: '🙏',
};

export const REACTION_TYPES: ReactionType[] = [
  'like',
  'love',
  'haha',
  'wow',
  'sad',
  'angry',
  'thanks',
];
```

## Lưu ý khi sử dụng:

1. **Authentication**: Đảm bảo token được lưu an toàn. Có thể sử dụng httpOnly cookies thay vì localStorage.

2. **Error Handling**: Thêm proper error handling và user feedback trong production.

3. **File Upload**: Cần implement endpoint upload file trên backend hoặc tích hợp với service bên thứ ba.

4. **Broadcasting Auth**: Đảm bảo endpoint `/api/broadcasting/auth` được setup đúng trên Laravel backend.

5. **Environment Variables**: Cập nhật các biến môi trường theo cấu hình thực tế của bạn.

6. **Type Safety**: Import types từ file `chat-type.ts` đã tạo sẵn.
