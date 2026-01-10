# Realtime Groups Feature - Implementation Guide

Hướng dẫn triển khai tính năng realtime cho Groups (Nhóm Học Tập) với like, reaction, comment, share và chat.

---

## 🚀 TỔNG QUAN

### Tính năng đã bổ sung:
- ✅ **Like & Reactions** realtime cho posts và comments
- ✅ **Comments** realtime cho posts
- ✅ **Share posts** với realtime notification
- ✅ **WebSocket Service** để tái sử dụng code
- ✅ **Realtime API** để lấy connection info
- ✅ **Broadcasting channels** cho groups và global feed

---

## 📡 REALTIME EVENTS

### 1. Post Events

#### `post.created`
```javascript
// Channel: posts.global, group.{groupId}.posts
{
  post: {
    id: 1,
    content: "Bài viết mới",
    user: { id: 1, name: "John" },
    group: { id: 1, name: "Laravel Group" }
  },
  type: "post_created"
}
```

#### `post.reacted`
```javascript
// Channel: posts.global, group.{groupId}.posts
{
  post_id: 1,
  reaction: {
    id: 1,
    user: { id: 2, name: "Jane" },
    reaction_type: "like"
  },
  action: "added", // or "removed"
  reactions_count: 5,
  type: "post_reacted"
}
```

#### `post.shared`
```javascript
// Channel: posts.global, group.{groupId}.posts
{
  original_post: { ... },
  shared_post: { ... },
  shared_by: { id: 3, name: "Bob" },
  type: "post_shared"
}
```

### 2. Comment Events

#### `comment.created`
```javascript
// Channel: posts.global, group.{groupId}.posts
{
  comment: {
    id: 1,
    content: "Bình luận mới",
    user: { id: 2, name: "Jane" }
  },
  post_id: 1,
  comments_count: 3,
  type: "comment_created"
}
```

#### `comment.reacted`
```javascript
// Channel: posts.global, group.{groupId}.posts
{
  comment_id: 1,
  post_id: 1,
  reaction: {
    id: 2,
    user: { id: 3, name: "Bob" },
    reaction_type: "love"
  },
  action: "added",
  reactions_count: 2,
  type: "comment_reacted"
}
```

---

## 🔌 API ENDPOINTS

### Realtime Connection
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

### Test Connection
```http
POST /api/realtime/test-connection
Authorization: Bearer {token}
```

### Share Post
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

---

## 🎯 FRONTEND IMPLEMENTATION

### 1. Setup Laravel Echo

```typescript
// lib/echo.ts
import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

window.Pusher = Pusher;

const echo = new Echo({
  broadcaster: 'reverb',
  key: process.env.NEXT_PUBLIC_REVERB_APP_KEY,
  wsHost: process.env.NEXT_PUBLIC_REVERB_HOST,
  wsPort: process.env.NEXT_PUBLIC_REVERB_PORT,
  wssPort: process.env.NEXT_PUBLIC_REVERB_PORT,
  forceTLS: (process.env.NEXT_PUBLIC_REVERB_SCHEME ?? 'https') === 'https',
  enabledTransports: ['ws', 'wss'],
  auth: {
    headers: {
      Authorization: `Bearer ${token}`,
    },
  },
});

export default echo;
```

### 2. Realtime Hook

```typescript
// hooks/useRealtime.ts
import { useEffect, useState } from 'react';
import echo from '@/lib/echo';
import { api } from '@/lib/api';

export function useRealtime() {
  const [connectionInfo, setConnectionInfo] = useState(null);
  const [isConnected, setIsConnected] = useState(false);

  useEffect(() => {
    // Get connection info
    api.get('/realtime/connection-info')
      .then(response => {
        setConnectionInfo(response.data);
        setIsConnected(true);
      })
      .catch(console.error);

    // Connection events
    echo.connector.pusher.connection.bind('connected', () => {
      setIsConnected(true);
    });

    echo.connector.pusher.connection.bind('disconnected', () => {
      setIsConnected(false);
    });

    return () => {
      echo.disconnect();
    };
  }, []);

  return { connectionInfo, isConnected };
}
```

### 3. Posts Realtime Hook

```typescript
// hooks/usePostsRealtime.ts
import { useEffect } from 'react';
import echo from '@/lib/echo';

export function usePostsRealtime({
  onPostCreated,
  onPostReacted,
  onPostShared,
  onCommentCreated,
  onCommentReacted,
  groupId = null
}) {
  useEffect(() => {
    const channels = ['posts.global'];
    if (groupId) {
      channels.push(`group.${groupId}.posts`);
    }

    const listeners = [];

    channels.forEach(channelName => {
      const channel = echo.channel(channelName);

      // Post events
      channel.listen('.post.created', onPostCreated);
      channel.listen('.post.reacted', onPostReacted);
      channel.listen('.post.shared', onPostShared);

      // Comment events
      channel.listen('.comment.created', onCommentCreated);
      channel.listen('.comment.reacted', onCommentReacted);

      listeners.push(channel);
    });

    return () => {
      listeners.forEach(channel => {
        echo.leaveChannel(channel.name);
      });
    };
  }, [groupId, onPostCreated, onPostReacted, onPostShared, onCommentCreated, onCommentReacted]);
}
```

### 4. Posts Feed Component

```typescript
// components/PostsFeed.tsx
import { useState, useEffect } from 'react';
import { usePostsRealtime } from '@/hooks/usePostsRealtime';
import { api } from '@/lib/api';

export function PostsFeed({ groupId = null }) {
  const [posts, setPosts] = useState([]);
  const [loading, setLoading] = useState(true);

  // Load initial posts
  useEffect(() => {
    const endpoint = groupId 
      ? `/posts/group/${groupId}` 
      : '/posts';
    
    api.get(endpoint)
      .then(response => {
        setPosts(response.data.data);
        setLoading(false);
      })
      .catch(console.error);
  }, [groupId]);

  // Realtime handlers
  const handlePostCreated = (event) => {
    setPosts(prev => [event.post, ...prev]);
  };

  const handlePostReacted = (event) => {
    setPosts(prev => prev.map(post => 
      post.id === event.post_id 
        ? { 
            ...post, 
            reactions_count: event.reactions_count,
            reactions: event.action === 'added' 
              ? [...post.reactions, event.reaction]
              : post.reactions.filter(r => r.id !== event.reaction?.id)
          }
        : post
    ));
  };

  const handlePostShared = (event) => {
    setPosts(prev => [event.shared_post, ...prev]);
  };

  const handleCommentCreated = (event) => {
    setPosts(prev => prev.map(post => 
      post.id === event.post_id 
        ? { ...post, comments_count: event.comments_count }
        : post
    ));
  };

  const handleCommentReacted = (event) => {
    // Update comment reactions if needed
    console.log('Comment reacted:', event);
  };

  // Setup realtime
  usePostsRealtime({
    onPostCreated: handlePostCreated,
    onPostReacted: handlePostReacted,
    onPostShared: handlePostShared,
    onCommentCreated: handleCommentCreated,
    onCommentReacted: handleCommentReacted,
    groupId
  });

  if (loading) return <div>Loading...</div>;

  return (
    <div className="posts-feed">
      {posts.map(post => (
        <PostCard key={post.id} post={post} />
      ))}
    </div>
  );
}
```

### 5. Post Card Component

```typescript
// components/PostCard.tsx
import { useState } from 'react';
import { api } from '@/lib/api';

export function PostCard({ post }) {
  const [isLiking, setIsLiking] = useState(false);
  const [showComments, setShowComments] = useState(false);

  const handleReact = async (reactionType) => {
    if (isLiking) return;
    setIsLiking(true);

    try {
      await api.post('/reactions', {
        target_type: 'post',
        target_id: post.id,
        reaction_type: reactionType
      });
    } catch (error) {
      console.error('React failed:', error);
    } finally {
      setIsLiking(false);
    }
  };

  const handleShare = async () => {
    try {
      await api.post(`/posts/${post.id}/share`, {
        content: 'Chia sẻ bài viết hay!',
        visibility: 'public'
      });
    } catch (error) {
      console.error('Share failed:', error);
    }
  };

  return (
    <div className="post-card">
      <div className="post-header">
        <img src={post.user.avatar} alt={post.user.name} />
        <div>
          <h4>{post.user.name}</h4>
          <span>{post.created_at}</span>
        </div>
      </div>

      <div className="post-content">
        {post.is_shared && (
          <div className="shared-indicator">
            Đã chia sẻ bài viết của {post.target.user.name}
          </div>
        )}
        <p>{post.content}</p>
        {post.target && (
          <div className="shared-post">
            <PostCard post={post.target} />
          </div>
        )}
      </div>

      <div className="post-stats">
        <span>{post.reactions_count} reactions</span>
        <span>{post.comments_count} comments</span>
      </div>

      <div className="post-actions">
        <button 
          onClick={() => handleReact('like')}
          disabled={isLiking}
          className={post.user_reaction === 'like' ? 'active' : ''}
        >
          👍 Like
        </button>
        <button onClick={() => setShowComments(!showComments)}>
          💬 Comment
        </button>
        <button onClick={handleShare}>
          📤 Share
        </button>
      </div>

      {showComments && (
        <CommentsSection postId={post.id} />
      )}
    </div>
  );
}
```

---

## 🔧 BACKEND SERVICES

### WebSocketService Usage

```php
// Trong controller
use App\Services\WebSocketService;

// Check permissions
if (!WebSocketService::canAccessGroupChannel($userId, $groupId)) {
    return response()->json(['error' => 'Unauthorized'], 403);
}

// Get user channels
$channels = WebSocketService::getUserGroupChannels($userId);

// Get connection info
$info = WebSocketService::getConnectionInfo();
```

---

## 🧪 TESTING

### 1. Test Realtime Connection

```bash
# Test connection endpoint
curl -X POST http://localhost:8000/api/realtime/test-connection \
  -H "Authorization: Bearer YOUR_TOKEN"
```

### 2. Test Events

```javascript
// Frontend console
echo.channel('posts.global')
  .listen('.test.connection', (event) => {
    console.log('Test event received:', event);
  });
```

### 3. Test Reactions

```bash
# Add reaction
curl -X POST http://localhost:8000/api/reactions \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"target_type":"post","target_id":1,"reaction_type":"like"}'

# Remove reaction
curl -X DELETE http://localhost:8000/api/reactions \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"target_type":"post","target_id":1}'
```

---

## 🚀 DEPLOYMENT

### Environment Variables

```env
# .env
BROADCAST_CONNECTION=reverb
REVERB_APP_ID=your-app-id
REVERB_APP_KEY=your-app-key
REVERB_APP_SECRET=your-app-secret
REVERB_HOST=localhost
REVERB_PORT=8080
REVERB_SCHEME=http

# Frontend .env.local
NEXT_PUBLIC_REVERB_APP_KEY=your-app-key
NEXT_PUBLIC_REVERB_HOST=localhost
NEXT_PUBLIC_REVERB_PORT=8080
NEXT_PUBLIC_REVERB_SCHEME=ws
```

### Start Reverb Server

```bash
php artisan reverb:start --host=0.0.0.0 --port=8080
```

---

## 📋 CHECKLIST

### Backend ✅
- [x] PostCreated event
- [x] PostReacted event  
- [x] PostShared event
- [x] CommentCreated event
- [x] CommentReacted event
- [x] WebSocketService
- [x] RealtimeController
- [x] Broadcasting channels
- [x] Share post endpoint
- [x] Reaction summary attributes

### Frontend (Cần implement)
- [ ] Laravel Echo setup
- [ ] useRealtime hook
- [ ] usePostsRealtime hook
- [ ] PostsFeed component
- [ ] PostCard component
- [ ] CommentsSection component
- [ ] Reaction buttons
- [ ] Share functionality
- [ ] Real-time notifications

---

## 💡 TIPS

1. **Performance**: Sử dụng Redis cho broadcasting trong production
2. **Security**: Verify permissions trong channels.php
3. **Error Handling**: Wrap broadcast calls trong try-catch
4. **Testing**: Test với nhiều users/browsers
5. **Monitoring**: Log realtime events để debug

---

## 🔗 RELATED FILES

- `app/Events/` - Realtime events
- `app/Services/WebSocketService.php` - WebSocket utilities
- `routes/channels.php` - Broadcasting channels
- `app/Http/Controllers/Api/RealtimeController.php` - Realtime API
- `GROUPS_README.md` - Groups documentation
- `CHAT_IMPLEMENTATION_GUIDE.md` - Chat realtime reference

---

**Hệ thống realtime cho Groups đã sẵn sàng! Bây giờ chỉ cần implement frontend để tận dụng các API và events đã tạo.**