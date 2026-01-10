# Hướng Dẫn Triển Khai Groups Feature - Frontend Next.js 15

Tài liệu này hướng dẫn chi tiết cách triển khai tính năng Groups (Nhóm Học Tập) trên frontend Next.js 15, tích hợp với backend Laravel API đã có.

---

## MỤC LỤC

1. [Cấu Trúc Project](#cấu-trúc-project)
2. [Setup Dependencies](#setup-dependencies)
3. [TypeScript Types](#typescript-types)
4. [API Client](#api-client)
5. [Custom Hooks](#custom-hooks)
6. [Components](#components)
7. [Pages & Routes](#pages--routes)
8. [Tích Hợp Chat](#tích-hợp-chat)
9. [Testing](#testing)
10. [Troubleshooting](#troubleshooting)

---

## CẤU TRÚC PROJECT

```
nextjs-project/
├── app/
│   ├── groups/
│   │   ├── page.tsx                    # Groups listing page
│   │   └── [slug]/
│   │       └── page.tsx               # Group detail page
│   └── layout.tsx
├── components/
│   ├── groups/
│   │   ├── GroupsList.tsx
│   │   ├── GroupCard.tsx
│   │   ├── GroupDetail.tsx
│   │   ├── GroupHeader.tsx
│   │   ├── GroupTabs.tsx
│   │   ├── GroupPostsTab.tsx
│   │   ├── GroupMembersTab.tsx
│   │   ├── GroupChatTab.tsx
│   │   ├── CreatePostForm.tsx
│   │   ├── PostCard.tsx
│   │   ├── MemberCard.tsx
│   │   └── JoinRequestModal.tsx
│   └── chat/
│       ├── ChatSidebar.tsx             # Updated với Group Chats
│       └── GroupChatItem.tsx
├── hooks/
│   ├── useGroups.ts
│   ├── useGroupDetail.ts
│   ├── useGroupPosts.ts
│   ├── useGroupMembers.ts
│   ├── useGroupChat.ts
│   └── useGroupJoinRequests.ts
├── lib/
│   ├── api.ts                          # API client
│   └── types.ts                        # TypeScript types
└── types/
    └── group.ts                        # Group-related types
```

---

## SETUP DEPENDENCIES

### Install Required Packages

```bash
npm install axios swr date-fns react-hook-form zod
# hoặc
pnpm add axios swr date-fns react-hook-form zod
```

### Environment Variables

Tạo file `.env.local`:

```env
NEXT_PUBLIC_API_URL=http://localhost:8000/api
NEXT_PUBLIC_REVERB_APP_KEY=your-reverb-key
NEXT_PUBLIC_REVERB_HOST=localhost
NEXT_PUBLIC_REVERB_PORT=8080
```

---

## TYPESCRIPT TYPES

Tạo file `types/group.ts`:

```typescript
export interface User {
  id: number;
  name: string;
  email?: string;
  avatar?: string | null;
}

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
  created_at: string;
  updated_at: string;
  user: User;
}

export interface Post {
  id: number;
  user_id: number;
  content: string | null;
  attachments: string[] | null;
  group_id: number | null;
  is_pinned: boolean;
  visibility: 'public' | 'private' | 'group_only';
  created_at: string;
  updated_at: string;
  user: User;
  comments_count: number;
  reactions_count: number;
  reactions?: Array<{
    id: number;
    user_id: number;
    reaction_type: string;
  }>;
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

export interface CreatePostRequest {
  content?: string;
  attachments?: string[];
  group_id: number;
  visibility?: 'public' | 'private' | 'group_only';
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
```
 
---

## API CLIENT

Cập nhật `lib/api.ts` với các endpoints cho Groups:

```typescript
import axios from 'axios';

const api = axios.create({
  baseURL: process.env.NEXT_PUBLIC_API_URL,
  headers: {
    'Content-Type': 'application/json',
  },
});

// Interceptor để thêm token
api.interceptors.request.use((config) => {
  const token = localStorage.getItem('auth_token'); // hoặc từ auth context
  if (token) {
    config.headers.Authorization = `Bearer ${token}`;
  }
  return config;
});

// Groups API
export const groupsApi = {
  // Lấy danh sách groups
  getGroups: (params?: {
    page?: number;
    search?: string;
    visibility?: 'public' | 'private';
  }) => api.get<PaginatedResponse<Group>>('/groups', { params }),

  // Tạo group mới
  createGroup: (data: CreateGroupRequest) =>
    api.post<{ message: string; data: Group }>('/groups', data),

  // Lấy chi tiết group
  getGroupDetail: (slug: string) =>
    api.get<GroupDetail>(`/groups/${slug}`),

  // Cập nhật group
  updateGroup: (groupId: number, data: UpdateGroupRequest) =>
    api.put(`/groups/${groupId}`, data),

  // Xóa group
  deleteGroup: (groupId: number) =>
    api.delete(`/groups/${groupId}`),

  // Join group
  joinGroup: (groupId: number) =>
    api.post(`/groups/${groupId}/join`),

  // Leave group
  leaveGroup: (groupId: number) =>
    api.post(`/groups/${groupId}/leave`),

  // Quản lý members
  kickMember: (groupId: number, userId: number) =>
    api.post(`/groups/${groupId}/kick/${userId}`),

  promoteMember: (groupId: number, userId: number) =>
    api.post(`/groups/${groupId}/promote/${userId}`),

  demoteMember: (groupId: number, userId: number) =>
    api.post(`/groups/${groupId}/demote/${userId}`),

  // Join requests
  getJoinRequests: (groupId: number) =>
    api.get<GroupJoinRequest[]>(`/groups/${groupId}/join-requests`),

  approveRequest: (requestId: number) =>
    api.post(`/groups/join-request/${requestId}/approve`),

  rejectRequest: (requestId: number) =>
    api.post(`/groups/join-request/${requestId}/reject`),
};

// Posts API
export const postsApi = {
  // Lấy posts của group
  getGroupPosts: (groupId: number, page = 1) =>
    api.get<PaginatedResponse<Post>>(`/posts/group/${groupId}`, {
      params: { page },
    }),

  // Tạo post
  createPost: (data: CreatePostRequest) =>
    api.post<{ message: string; data: Post }>('/posts', data),
};

// Chat API (cần bổ sung group thread endpoint)
export const chatApi = {
  // Lấy tất cả threads (bao gồm group threads)
  getThreads: () => api.get<ChatThread[]>('/chat/threads'),

  // Lấy group thread (nếu backend có endpoint này)
  getGroupThread: (groupId: number) =>
    api.get<ChatThread>(`/chat/threads/group/${groupId}`),
};
```

---

## CUSTOM HOOKS

### `hooks/useGroups.ts`

```typescript
import useSWR from 'swr';
import { groupsApi } from '@/lib/api';
import { Group, PaginatedResponse } from '@/types/group';

interface UseGroupsParams {
  search?: string;
  visibility?: 'public' | 'private';
  page?: number;
}

export function useGroups(params?: UseGroupsParams) {
  const { data, error, isLoading, mutate } = useSWR<PaginatedResponse<Group>>(
    ['groups', params],
    () => groupsApi.getGroups(params).then((res) => res.data)
  );

  const joinGroup = async (groupId: number) => {
    try {
      await groupsApi.joinGroup(groupId);
      mutate(); // Refresh list
    } catch (err) {
      throw err;
    }
  };

  const leaveGroup = async (groupId: number) => {
    try {
      await groupsApi.leaveGroup(groupId);
      mutate(); // Refresh list
    } catch (err) {
      throw err;
    }
  };

  return {
    groups: data?.data || [],
    pagination: data
      ? {
          currentPage: data.current_page,
          lastPage: data.last_page,
          total: data.total,
        }
      : null,
    isLoading,
    isError: error,
    joinGroup,
    leaveGroup,
    refresh: mutate,
  };
}
```

### `hooks/useGroupDetail.ts`

```typescript
import useSWR from 'swr';
import { groupsApi } from '@/lib/api';
import { GroupDetail } from '@/types/group';
import { useAuth } from '@/contexts/AuthContext'; // hoặc hook lấy current user

export function useGroupDetail(slug: string) {
  const { user } = useAuth(); // Lấy current user

  const { data, error, isLoading, mutate } = useSWR<GroupDetail>(
    ['group', slug],
    () => groupsApi.getGroupDetail(slug).then((res) => res.data),
    { revalidateOnFocus: false }
  );

  // Check membership
  const isMember = data?.members.some((m) => m.user_id === user?.id) || false;
  const isOwner = data?.owner_id === user?.id;
  const isAdmin =
    data?.members.find((m) => m.user_id === user?.id)?.role === 'admin' ||
    isOwner;
  const userRole = data?.members.find((m) => m.user_id === user?.id)?.role;

  const joinGroup = async () => {
    if (!data) return;
    try {
      await groupsApi.joinGroup(data.id);
      mutate(); // Refresh group detail
    } catch (err) {
      throw err;
    }
  };

  const leaveGroup = async () => {
    if (!data) return;
    try {
      await groupsApi.leaveGroup(data.id);
      mutate();
    } catch (err) {
      throw err;
    }
  };

  return {
    group: data,
    isLoading,
    isError: error,
    isMember,
    isOwner,
    isAdmin,
    userRole,
    joinGroup,
    leaveGroup,
    refresh: mutate,
  };
}
```

### `hooks/useGroupPosts.ts`

```typescript
import { useState } from 'react';
import useSWRInfinite from 'swr/infinite';
import { postsApi } from '@/lib/api';
import { Post, PaginatedResponse } from '@/types/group';

export function useGroupPosts(groupId: number) {
  const getKey = (pageIndex: number, previousPageData: PaginatedResponse<Post> | null) => {
    if (previousPageData && !previousPageData.data.length) return null;
    return ['group-posts', groupId, pageIndex + 1];
  };

  const { data, error, size, setSize, mutate } = useSWRInfinite<PaginatedResponse<Post>>(
    getKey,
    ([, , page]) => postsApi.getGroupPosts(groupId, page).then((res) => res.data)
  );

  const posts = data ? data.flatMap((page) => page.data) : [];
  const isLoadingMore = size > 0 && data && typeof data[size - 1] === 'undefined';
  const hasMore = data
    ? data[data.length - 1].current_page < data[data.length - 1].last_page
    : false;

  const createPost = async (content: string, attachments?: string[]) => {
    try {
      const response = await postsApi.createPost({
        content,
        attachments,
        group_id: groupId,
      });
      mutate(); // Refresh posts
      return response.data.data;
    } catch (err) {
      throw err;
    }
  };

  return {
    posts,
    isLoading: !data && !error,
    isError: error,
    loadMore: () => setSize(size + 1),
    hasMore,
    isLoadingMore,
    createPost,
    refresh: mutate,
  };
}
```

### `hooks/useGroupChat.ts`

```typescript
import { useEffect, useState } from 'react';
import useSWR from 'swr';
import { chatApi } from '@/lib/api';
import { ChatThread, ChatMessage } from '@/types/chat'; // Từ chat system
import { useEcho } from '@/hooks/useEcho'; // Hook setup Echo

export function useGroupChat(groupId: number) {
  const echo = useEcho();
  const [messages, setMessages] = useState<ChatMessage[]>([]);
  const [typingUsers, setTypingUsers] = useState<string[]>([]);

  // Lấy group thread
  const { data: thread, mutate: refreshThread } = useSWR<ChatThread>(
    ['group-thread', groupId],
    async () => {
      // Option 1: Nếu có endpoint riêng
      // return chatApi.getGroupThread(groupId).then(res => res.data);

      // Option 2: Filter từ all threads
      const threads = await chatApi.getThreads().then((res) => res.data);
      return (
        threads.find((t) => t.type === 'group' && t.group_id === groupId) ||
        null
      );
    }
  );

  // Load messages
  useEffect(() => {
    if (!thread) return;

    const loadMessages = async () => {
      try {
        const response = await chatApi.getMessages(thread.id);
        setMessages(response.data.data);
      } catch (err) {
        console.error('Failed to load messages:', err);
      }
    };

    loadMessages();
  }, [thread]);

  // Subscribe to realtime events
  useEffect(() => {
    if (!thread || !echo) return;

    const channel = echo.private(`chat.thread.${thread.id}`);

    channel
      .listen('.message.created', (e: { message: ChatMessage }) => {
        setMessages((prev) => [...prev, e.message]);
      })
      .listen('.user.typing', (e: { userName: string }) => {
        setTypingUsers((prev) => {
          if (!prev.includes(e.userName)) {
            return [...prev, e.userName];
          }
          return prev;
        });
        // Clear typing after 3 seconds
        setTimeout(() => {
          setTypingUsers((prev) => prev.filter((u) => u !== e.userName));
        }, 3000);
      });

    return () => {
      channel.stopListening('.message.created');
      channel.stopListening('.user.typing');
    };
  }, [thread, echo]);

  const sendMessage = async (content: string, attachments?: string[]) => {
    if (!thread) return;

    try {
      const response = await chatApi.sendMessage(thread.id, {
        content,
        attachments,
      });
      // Message sẽ được thêm qua realtime event
      return response.data;
    } catch (err) {
      throw err;
    }
  };

  const markAsRead = async () => {
    if (!thread) return;
    try {
      await chatApi.markAsRead(thread.id);
    } catch (err) {
      console.error('Failed to mark as read:', err);
    }
  };

  return {
    thread,
    messages,
    typingUsers,
    sendMessage,
    markAsRead,
    refreshThread,
  };
}
```

---

## COMPONENTS

### `components/groups/GroupsList.tsx`

```typescript
'use client';

import { useState } from 'react';
import { useGroups } from '@/hooks/useGroups';
import { GroupCard } from './GroupCard';
import { Search, Filter } from 'lucide-react';

export function GroupsList() {
  const [search, setSearch] = useState('');
  const [visibility, setVisibility] = useState<'public' | 'private' | 'all'>('all');

  const { groups, isLoading, pagination, joinGroup, leaveGroup } = useGroups({
    search: search || undefined,
    visibility: visibility !== 'all' ? visibility : undefined,
  });

  return (
    <div className="container mx-auto px-4 py-8">
      <div className="mb-6">
        <h1 className="text-3xl font-bold mb-4">Khám Phá Nhóm</h1>
        
        {/* Search & Filter */}
        <div className="flex gap-4">
          <div className="flex-1 relative">
            <Search className="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400" />
            <input
              type="text"
              placeholder="Tìm kiếm nhóm..."
              value={search}
              onChange={(e) => setSearch(e.target.value)}
              className="w-full pl-10 pr-4 py-2 border rounded-lg"
            />
          </div>
          <select
            value={visibility}
            onChange={(e) => setVisibility(e.target.value as any)}
            className="px-4 py-2 border rounded-lg"
          >
            <option value="all">Tất cả</option>
            <option value="public">Công khai</option>
            <option value="private">Riêng tư</option>
          </select>
        </div>
      </div>

      {/* Groups Grid */}
      {isLoading ? (
        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
          {[...Array(6)].map((_, i) => (
            <div key={i} className="h-64 bg-gray-200 animate-pulse rounded-lg" />
          ))}
        </div>
      ) : groups.length === 0 ? (
        <div className="text-center py-12">
          <p className="text-gray-500">Không tìm thấy nhóm nào</p>
        </div>
      ) : (
        <>
          <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            {groups.map((group) => (
              <GroupCard
                key={group.id}
                group={group}
                onJoin={() => joinGroup(group.id)}
                onLeave={() => leaveGroup(group.id)}
              />
            ))}
          </div>
          
          {/* Pagination */}
          {pagination && pagination.lastPage > 1 && (
            <div className="mt-8 flex justify-center gap-2">
              {/* Pagination buttons */}
            </div>
          )}
        </>
      )}
    </div>
  );
}
```

### `components/groups/GroupCard.tsx`

```typescript
'use client';

import Link from 'next/link';
import Image from 'next/image';
import { Group } from '@/types/group';
import { Users, Lock, Globe } from 'lucide-react';

interface GroupCardProps {
  group: Group;
  onJoin: () => void;
  onLeave: () => void;
  isMember?: boolean;
}

export function GroupCard({ group, onJoin, onLeave, isMember }: GroupCardProps) {
  return (
    <Link href={`/groups/${group.slug}`}>
      <div className="bg-white rounded-lg shadow-md overflow-hidden hover:shadow-lg transition-shadow">
        {/* Cover Image */}
        <div className="relative h-32 bg-gradient-to-r from-blue-400 to-purple-500">
          {group.cover_image && (
            <Image
              src={group.cover_image}
              alt={group.name}
              fill
              className="object-cover"
            />
          )}
        </div>

        {/* Content */}
        <div className="p-4">
          <h3 className="font-semibold text-lg mb-2">{group.name}</h3>
          <p className="text-sm text-gray-600 line-clamp-2 mb-4">
            {group.description || 'Không có mô tả'}
          </p>

          {/* Stats */}
          <div className="flex items-center gap-4 text-sm text-gray-500 mb-4">
            <div className="flex items-center gap-1">
              <Users className="w-4 h-4" />
              <span>{group.members_count} thành viên</span>
            </div>
            <div className="flex items-center gap-1">
              {group.visibility === 'public' ? (
                <Globe className="w-4 h-4" />
              ) : (
                <Lock className="w-4 h-4" />
              )}
              <span className="capitalize">{group.visibility}</span>
            </div>
          </div>

          {/* Action Button */}
          <button
            onClick={(e) => {
              e.preventDefault();
              isMember ? onLeave() : onJoin();
            }}
            className={`w-full py-2 rounded-lg font-medium transition-colors ${
              isMember
                ? 'bg-red-500 text-white hover:bg-red-600'
                : 'bg-blue-500 text-white hover:bg-blue-600'
            }`}
          >
            {isMember ? 'Đã tham gia' : 'Tham gia'}
          </button>
        </div>
      </div>
    </Link>
  );
}
```

### `components/groups/GroupDetail.tsx`

```typescript
'use client';

import { useGroupDetail } from '@/hooks/useGroupDetail';
import { GroupHeader } from './GroupHeader';
import { GroupTabs } from './GroupTabs';
import { useState } from 'react';

interface GroupDetailProps {
  slug: string;
}

export function GroupDetail({ slug }: GroupDetailProps) {
  const [activeTab, setActiveTab] = useState<'posts' | 'members' | 'chat'>('posts');
  const { group, isLoading, isMember, isOwner, joinGroup, leaveGroup } =
    useGroupDetail(slug);

  if (isLoading) {
    return <div>Loading...</div>;
  }

  if (!group) {
    return <div>Group not found</div>;
  }

  return (
    <div className="min-h-screen bg-gray-50">
      <GroupHeader
        group={group}
        isMember={isMember}
        isOwner={isOwner}
        onJoin={joinGroup}
        onLeave={leaveGroup}
      />

      <div className="container mx-auto px-4 py-6">
        <GroupTabs
          activeTab={activeTab}
          onTabChange={setActiveTab}
          group={group}
          isMember={isMember}
        />
      </div>
    </div>
  );
}
```

---

## PAGES & ROUTES

### `app/groups/page.tsx`

```typescript
import { GroupsList } from '@/components/groups/GroupsList';

export default function GroupsPage() {
  return <GroupsList />;
}
```

### `app/groups/[slug]/page.tsx`

```typescript
import { GroupDetail } from '@/components/groups/GroupDetail';

export default function GroupDetailPage({
  params,
}: {
  params: { slug: string };
}) {
  return <GroupDetail slug={params.slug} />;
}
```

---

## TÍCH HỢP CHAT

### Update `components/chat/ChatSidebar.tsx`

Thêm section "Group Chats":

```typescript
'use client';

import { useChatThreads } from '@/hooks/useChatThreads';
import { GroupChatItem } from './GroupChatItem';
import { DirectChatItem } from './DirectChatItem';

export function ChatSidebar() {
  const { threads } = useChatThreads();

  const directThreads = threads.filter((t) => t.type === 'direct');
  const groupThreads = threads.filter(
    (t) => t.type === 'group' && t.group_id !== null
  );

  return (
    <div className="w-80 border-r bg-white h-screen overflow-y-auto">
      <div className="p-4 border-b">
        <h2 className="font-semibold text-lg">Chat</h2>
      </div>

      {/* Direct Messages */}
      <div className="p-2">
        <h3 className="text-xs font-semibold text-gray-500 uppercase px-2 mb-2">
          Tin nhắn
        </h3>
        {directThreads.map((thread) => (
          <DirectChatItem key={thread.id} thread={thread} />
        ))}
      </div>

      {/* Group Chats */}
      {groupThreads.length > 0 && (
        <div className="p-2 border-t">
          <h3 className="text-xs font-semibold text-gray-500 uppercase px-2 mb-2">
            Nhóm
          </h3>
          {groupThreads.map((thread) => (
            <GroupChatItem key={thread.id} thread={thread} />
          ))}
        </div>
      )}
    </div>
  );
}
```

### `components/chat/GroupChatItem.tsx`

```typescript
'use client';

import Link from 'next/link';
import { ChatThread } from '@/types/chat';
import { formatDistanceToNow } from 'date-fns';
import { vi } from 'date-fns/locale';

interface GroupChatItemProps {
  thread: ChatThread;
}

export function GroupChatItem({ thread }: GroupChatItemProps) {
  const lastMessage = thread.messages?.[0]; // Assuming messages are loaded
  const unreadCount = 0; // Calculate from last_read_at

  return (
    <Link href={`/groups/${thread.group_id}?tab=chat`}>
      <div className="flex items-center gap-3 p-3 hover:bg-gray-50 rounded-lg cursor-pointer">
        {/* Avatar */}
        <div className="w-12 h-12 rounded-full bg-gradient-to-r from-blue-400 to-purple-500 flex items-center justify-center text-white font-semibold">
          {thread.name?.[0].toUpperCase()}
        </div>

        {/* Content */}
        <div className="flex-1 min-w-0">
          <div className="flex items-center justify-between">
            <p className="font-medium truncate">{thread.name}</p>
            {lastMessage && (
              <span className="text-xs text-gray-500">
                {formatDistanceToNow(new Date(lastMessage.created_at), {
                  addSuffix: true,
                  locale: vi,
                })}
              </span>
            )}
          </div>
          {lastMessage && (
            <p className="text-sm text-gray-600 truncate">
              {lastMessage.user?.name}: {lastMessage.content}
            </p>
          )}
        </div>

        {/* Unread Badge */}
        {unreadCount > 0 && (
          <div className="w-5 h-5 rounded-full bg-blue-500 text-white text-xs flex items-center justify-center">
            {unreadCount}
          </div>
        )}
      </div>
    </Link>
  );
}
```

---

## TESTING

### Test Cases

1. **Groups Listing**
   - Load groups list
   - Search functionality
   - Filter by visibility
   - Join/Leave from listing

2. **Group Detail**
   - Load group info
   - Switch tabs
   - Join/Leave group
   - View posts, members, chat

3. **Group Chat**
   - Load chat thread
   - Send/receive messages
   - Typing indicators
   - Unread count

4. **Sidebar Integration**
   - Group chats appear in sidebar
   - Navigate from sidebar

---

## TROUBLESHOOTING

### Group Chat Thread không tồn tại

**Vấn đề:** Khi vào tab Chat, không có thread.

**Giải pháp:**
1. Backend cần auto-create thread khi tạo group
2. Hoặc tạo endpoint để get/create group thread
3. Frontend: Check nếu không có thread → Hiển thị message "Chat sẽ được tạo khi bạn tham gia nhóm"

### Unread count không chính xác

**Vấn đề:** Unread count không cập nhật.

**Giải pháp:**
- Tính từ `last_read_at` trong `ChatParticipant`
- So sánh với `last message.created_at`
- Update khi mark as read

### Performance Issues

**Vấn đề:** List quá nhiều groups/posts.

**Giải pháp:**
- Implement virtual scrolling
- Pagination thay vì infinite scroll
- Lazy load images
- Debounce search

---

## NEXT STEPS

1. **Backend Enhancements:**
   - Auto-create group chat thread
   - Add `posts_count` to Group model
   - Add endpoint `GET /api/chat/threads/group/{groupId}`

2. **Frontend Enhancements:**
   - Add notifications for join requests
   - Add group settings page
   - Add group analytics (owner only)
   - Add file upload for cover image

3. **Testing:**
   - Unit tests cho hooks
   - Integration tests cho components
   - E2E tests cho flows

---

**Tài liệu này cung cấp hướng dẫn chi tiết để triển khai tính năng Groups. Hãy tham khảo `GROUPS_UI_PROMPT.md` để biết thêm về UI/UX requirements.**

