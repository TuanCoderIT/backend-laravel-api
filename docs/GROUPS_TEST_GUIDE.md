# Groups Test Page - Hướng dẫn sử dụng

Trang test để demo và kiểm tra các tính năng comment, reaction, share trong Groups.

---

## 🚀 TRUY CẬP

### URL
```
http://localhost:8000/groups/test
```

---

## 🔑 AUTHENTICATION

### 1. Lấy Bearer Token
Trước tiên cần lấy token từ API:

```bash
# Login để lấy token
curl -X POST http://localhost:8000/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{
    "email": "user@example.com",
    "password": "password"
  }'
```

### 2. Nhập Token vào trang
- Copy token từ response
- Paste vào ô "Enter Bearer Token"
- Click "Set Token"
- Sẽ hiển thị tên user nếu thành công

---

## 📋 TÍNH NĂNG ĐÃ CÓ

### ✅ Groups Management
- **Load Groups**: Xem danh sách tất cả groups
- **View Posts**: Xem posts của group cụ thể
- Hiển thị: tên, mô tả, số members, số posts

### ✅ Posts Feed
- **Global Feed**: Tất cả posts public
- **Group Feed**: Posts của group cụ thể
- **Create Post**: Tạo post mới (public/group/private)
- **Share Post**: Chia sẻ post với message tùy chọn

### ✅ Reactions System
- **Like, Love, Haha**: React cho posts và comments
- **Real-time count**: Số lượng reactions update ngay
- **Visual feedback**: Hiển thị emoji và số lượng
- **API**: `POST /api/reactions`, `GET /api/reactions`

### ✅ Comments System
- **View Comments**: Xem tất cả comments của post
- **Add Comment**: Thêm comment mới
- **React to Comments**: Like comments
- **Real-time**: Comments xuất hiện ngay khi tạo
- **API**: `GET /api/posts/{id}/comments`, `POST /api/posts/{id}/comments`

---

## 🎯 CÁCH SỬ DỤNG

### 1. Setup Authentication
```
1. Nhập token vào ô input
2. Click "Set Token"
3. Verify hiển thị tên user
```

### 2. Xem Groups
```
1. Click "Load Groups"
2. Chọn group để xem posts
3. Hoặc nhập Group ID và click "Load Group Posts"
```

### 3. Tương tác với Posts
```
1. Click reaction buttons (👍❤️😂)
2. Click "Comment" để mở/đóng comments
3. Nhập comment và click "Post"
4. Click "Share" để chia sẻ post
```

### 4. Tạo Post mới
```
1. Nhập nội dung vào textarea
2. Chọn Group ID (optional)
3. Chọn visibility
4. Click "Post"
```

---

## 🔧 API ENDPOINTS ĐƯỢC SỬ DỤNG

### Posts
```http
GET /api/posts                    # Global feed
GET /api/posts/group/{id}         # Group feed
POST /api/posts                   # Create post
POST /api/posts/{id}/share        # Share post
```

### Comments
```http
GET /api/posts/{id}/comments      # Get comments
POST /api/posts/{id}/comments     # Add comment
DELETE /api/comments/{id}         # Delete comment
```

### Reactions
```http
POST /api/reactions               # Add/update reaction
DELETE /api/reactions             # Remove reaction
GET /api/reactions                # Get reactions list
```

### Groups
```http
GET /api/groups                   # List groups
GET /api/groups/{slug}            # Group detail
```

---

## 🧪 TEST SCENARIOS

### 1. Basic Flow
```
1. Set token
2. Load groups
3. Create a post
4. React to post
5. Add comment
6. React to comment
7. Share post
```

### 2. Multi-user Testing
```
1. Open 2 browser tabs
2. Use different tokens
3. One user creates post
4. Other user reacts/comments
5. Verify real-time updates
```

### 3. Group Testing
```
1. Join a group
2. Create group-only post
3. Verify visibility
4. Test group feed
```

---

## 🐛 TROUBLESHOOTING

### Token Issues
```
- Error: "Unauthenticated"
- Solution: Check token format, ensure "Bearer " prefix
- Verify token is valid and not expired
```

### API Errors
```
- Check browser console for detailed errors
- Verify API endpoints are working
- Check Laravel logs: storage/logs/laravel.log
```

### No Data Showing
```
- Ensure database has sample data
- Run seeders if needed: php artisan db:seed
- Check API responses in Network tab
```

---

## 📊 SAMPLE DATA

### Create Sample Posts (via API)
```bash
# Create a post
curl -X POST http://localhost:8000/api/posts \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "content": "Hello from API!",
    "visibility": "public"
  }'
```

### Create Sample Reactions
```bash
# React to post
curl -X POST http://localhost:8000/api/reactions \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "target_type": "post",
    "target_id": 1,
    "reaction_type": "like"
  }'
```

---

## 🚀 NEXT STEPS

### Frontend Improvements Needed
- [ ] Real-time updates với Laravel Echo
- [ ] Better UI/UX design
- [ ] Image upload support
- [ ] Pagination for posts/comments
- [ ] User avatars
- [ ] Notification system

### Backend Enhancements
- [ ] Reply to comments (nested comments)
- [ ] Edit/delete posts and comments
- [ ] Post privacy settings
- [ ] Mention users (@username)
- [ ] File attachments

---

## 💡 TIPS

1. **Performance**: Limit posts loaded (pagination)
2. **UX**: Add loading states for better feedback
3. **Security**: Always validate permissions
4. **Testing**: Use different user accounts
5. **Debug**: Check browser console and network tab

---

**Trang test này cho phép bạn kiểm tra đầy đủ các tính năng comment, reaction, share trong Groups mà không cần frontend framework phức tạp!**