# Posts Upgrade Prompt - Reactions & Comments

## 🎯 MỤC TIÊU
Nâng cấp hệ thống Posts với reactions (like, love, haha, sad, angry) và comments có realtime updates.

## 📡 API ENDPOINTS CẦN IMPLEMENT

### Posts
```
GET /api/posts                     # Feed chung (đã có)
GET /api/posts/group/{id}          # Feed nhóm (đã có)  
POST /api/posts                    # Tạo post (đã có)
POST /api/posts/{id}/share         # Share post (đã có)
```

### Reactions
```
POST /api/reactions                # Thêm/cập nhật reaction
DELETE /api/reactions              # Xóa reaction
GET /api/reactions                 # Lấy danh sách reactions
```

### Comments  
```
GET /api/posts/{id}/comments       # Lấy comments của post
POST /api/posts/{id}/comments      # Thêm comment
DELETE /api/comments/{id}          # Xóa comment
```

## 🔄 LUỒNG HOẠT ĐỘNG

### 1. Reactions Flow
```
User click reaction → POST /api/reactions → Broadcast event → Update UI realtime
- Support: like, love, haha, wow, sad, angry
- Toggle: click lại để bỏ reaction
- Show count và emoji cho mỗi loại
```

### 2. Comments Flow
```
User type comment → POST /api/posts/{id}/comments → Broadcast event → Show comment realtime
- Nested comments (replies) support
- Show user avatar, name, timestamp
- Like comments (reactions on comments)
```

### 3. Realtime Events
```
- post.reacted: {post_id, reaction, action, count}
- comment.created: {comment, post_id, count}  
- comment.reacted: {comment_id, reaction, action, count}
```

## 📊 DATA STRUCTURE

### Post Response
```json
{
  "id": 1,
  "content": "...",
  "reactions_count": 5,
  "comments_count": 3,
  "user_reaction": "like",
  "reaction_summary": {"like": 3, "love": 2}
}
```

### Comment Response
```json
{
  "id": 1,
  "content": "...",
  "user": {"id": 1, "name": "John"},
  "reactions_count": 2,
  "user_reaction": "like",
  "replies": [...]
}
```

## 🎨 UI COMPONENTS CẦN

### PostCard
- Reaction buttons row (👍❤️😂😮😢😠)
- Comments toggle button
- Share button
- Reactions summary display

### CommentSection  
- Comment input với submit button
- Comments list với nested replies
- Reaction buttons cho từng comment

### ReactionButton
- Emoji + count display
- Active state khi user đã react
- Hover effects

## 🔧 TECHNICAL REQUIREMENTS

### Backend
- Polymorphic reactions (Post, Comment)
- Realtime broadcasting với Laravel Echo
- Efficient queries với eager loading
- Permission checks

### Frontend
- Optimistic UI updates
- Realtime listeners
- Infinite scroll cho comments
- Debounced API calls

## 📋 IMPLEMENTATION ORDER
1. Setup reactions API endpoints
2. Add comments API endpoints  
3. Implement realtime broadcasting
4. Create UI components
5. Add realtime listeners
6. Test & optimize

**Kết quả: Posts system hoạt động như Facebook/Instagram với đầy đủ tương tác realtime.**