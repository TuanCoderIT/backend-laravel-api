/**
 * TypeScript Types cho Chat Realtime System
 * Sử dụng các types này trong Next.js frontend
 */

// ==================== API Response Types ====================

export interface ChatThread {
    id: number;
    type: 'direct' | 'group';
    name?: string | null;
    owner_id?: number | null;
    group_id?: number | null;
    course_id?: number | null;
    created_at: string;
    updated_at: string;
    participants: ChatParticipant[];
  }
  
  export interface ChatParticipant {
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
  }
  
  export interface ChatMessage {
    id: number;
    thread_id: number;
    user_id: number;
    content: string | null;
    attachments: string[] | null;
    created_at: string;
    updated_at: string;
    user: {
      id: number;
      name: string;
    };
    reactions?: Reaction[];
  }
  
  export interface Reaction {
    id: number;
    user_id: number;
    reaction_type: string; // 'like', 'love', 'haha', 'wow', 'sad', 'angry', etc.
    user?: {
      id: number;
      name: string;
    };
  }
  
  export interface PaginatedResponse<T> {
    data: T[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    from: number;
    to: number;
  }
  
  // ==================== API Request Types ====================
  
  export interface CreateDirectThreadRequest {
    user_id: number;
  }
  
  export interface SendMessageRequest {
    content?: string;
    attachments?: string[];
  }
  
  export interface ReactMessageRequest {
    reaction_type: string;
  }
  
  export interface MessagesQueryParams {
    limit?: number;
    page?: number;
  }
  
  // ==================== Broadcasting Event Types ====================
  
  export interface MessageCreatedEvent {
    message: ChatMessage;
    threadId: number;
  }
  
  export interface ThreadReadEvent {
    threadId: number;
    userId: number;
    userName: string;
  }
  
  export interface UserTypingEvent {
    threadId: number;
    userId: number;
    userName: string;
  }
  
  // ==================== UI State Types ====================
  
  export interface TypingUser {
    userId: number;
    userName: string;
    timestamp: number; // để auto-remove sau vài giây
  }
  
  export interface ChatState {
    threads: ChatThread[];
    currentThreadId: number | null;
    messages: Record<number, ChatMessage[]>; // threadId -> messages
    typingUsers: Record<number, TypingUser[]>; // threadId -> typing users
    unreadCounts: Record<number, number>; // threadId -> unread count
    isLoading: boolean;
    isSending: boolean;
  }
  
  // ==================== Component Props Types ====================
  
  export interface ThreadListProps {
    threads: ChatThread[];
    currentThreadId: number | null;
    onSelectThread: (threadId: number) => void;
    unreadCounts: Record<number, number>;
  }
  
  export interface ThreadItemProps {
    thread: ChatThread;
    isActive: boolean;
    unreadCount: number;
    onClick: () => void;
    currentUserId: number;
  }
  
  export interface ChatWindowProps {
    thread: ChatThread | null;
    messages: ChatMessage[];
    currentUserId: number;
    onSendMessage: (content: string, attachments?: string[]) => Promise<void>;
    onMarkAsRead: () => Promise<void>;
    onReact: (messageId: number, reactionType: string) => Promise<void>;
    onRemoveReaction: (messageId: number) => Promise<void>;
    typingUsers: TypingUser[];
  }
  
  export interface MessageBubbleProps {
    message: ChatMessage;
    isOwn: boolean;
    showAvatar: boolean;
    showName: boolean;
    onReact: (reactionType: string) => void;
    currentUserId: number;
  }
  
  export interface MessageInputProps {
    onSend: (content: string, attachments?: string[]) => Promise<void>;
    onTyping: () => void;
    disabled?: boolean;
  }
  
  // ==================== Utility Types ====================
  
  export type ReactionType = 'like' | 'love' | 'haha' | 'wow' | 'sad' | 'angry' | 'thanks';
  
  export interface ApiError {
    message: string;
    errors?: Record<string, string[]>;
  }
  
  // ==================== Hook Return Types ====================
  
  export interface UseChatReturn {
    threads: ChatThread[];
    currentThread: ChatThread | null;
    messages: ChatMessage[];
    isLoading: boolean;
    isSending: boolean;
    typingUsers: TypingUser[];
    unreadCounts: Record<number, number>;
    selectThread: (threadId: number) => void;
    sendMessage: (content: string, attachments?: string[]) => Promise<void>;
    markAsRead: () => Promise<void>;
    reactToMessage: (messageId: number, reactionType: string) => Promise<void>;
    removeReaction: (messageId: number) => Promise<void>;
    loadMoreMessages: () => Promise<void>;
    hasMoreMessages: boolean;
  }
  