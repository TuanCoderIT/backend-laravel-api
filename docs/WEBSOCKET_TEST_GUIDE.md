# Hướng Dẫn Test WebSocket Realtime

## File Test

File HTML test đã được tạo tại: `public/websocket-test.html`

Truy cập: `http://localhost:8000/websocket-test.html`

## Cách Sử Dụng

### 1. Chuẩn Bị

#### Lấy Reverb App Key
Kiểm tra file `.env`:
```env
REVERB_APP_KEY=your-app-key-here
```

Hoặc chạy lệnh:
```bash
php artisan tinker
>>> config('broadcasting.connections.reverb.key')
```

#### Lấy Auth Token
Đăng nhập và lấy token từ API:
```bash
curl -X POST http://localhost:8000/api/login \
  -H "Content-Type: application/json" \
  -d '{"email":"user@example.com","password":"password"}'
```

Response sẽ có token, copy token đó.

### 2. Điền Thông Tin

Mở `http://localhost:8000/websocket-test.html` và điền:

1. **API URL**: `http://localhost:8000/api`
2. **Reverb Host**: `localhost`
3. **Reverb Port**: `8080` (hoặc port bạn đã config)
4. **Reverb App Key**: Lấy từ `.env` (REVERB_APP_KEY)
5. **Auth Token**: Token từ bước đăng nhập (chỉ cần token, không cần "Bearer")
6. **Thread ID**: ID của thread muốn test (ví dụ: 1)

### 3. Kết Nối

1. Click nút **"Connect"**
2. Xem log panel phía dưới để kiểm tra trạng thái
3. Nếu thành công, sẽ thấy:
   - ✅ Status: "Connected" (màu xanh)
   - ✅ Log: "Connected to Reverb server"
   - ✅ Log: "Subscribed to channel: private-chat.thread.1"

### 4. Test Gửi Tin Nhắn

1. Nhập tin nhắn vào ô input
2. Click "Gửi" hoặc nhấn Enter
3. Tin nhắn sẽ:
   - Hiển thị ngay trong chat area (optimistic update)
   - Được gửi lên server qua API
   - Server broadcast event qua WebSocket
   - Nhận lại event và hiển thị (nếu có user khác subscribe)

### 5. Test Realtime (2 Tab)

1. **Tab 1**: Mở `websocket-test.html`, connect và gửi tin nhắn
2. **Tab 2**: Mở `websocket-test.html` với user khác (token khác), connect cùng thread ID
3. Gửi tin nhắn từ Tab 1 → Tab 2 sẽ nhận được ngay lập tức (realtime)

### 6. Test Typing Indicator

1. Mở 2 tab với 2 user khác nhau
2. Tab 1: Bắt đầu gõ trong input (chưa gửi)
3. Tab 2: Sẽ thấy "User đang gõ..." indicator

**Lưu ý**: Typing indicator chỉ hoạt động nếu bạn implement logic gửi typing event khi user gõ (không có trong HTML test này, cần thêm).

## Tính Năng

### ✅ Đã Implement

- Kết nối WebSocket với Laravel Reverb
- Subscribe vào private channel
- Nhận và hiển thị messages realtime
- Gửi tin nhắn qua API
- Hiển thị typing indicator
- Log panel để debug
- Auto-save config vào localStorage
- Connection status indicator

### 📝 Cần Thêm (Optional)

- Auto-load messages khi connect
- Typing event khi user gõ (debounce)
- Read receipts
- Reactions
- File attachments
- Multiple threads list

## Troubleshooting

### Không kết nối được

1. **Kiểm tra Reverb server đang chạy:**
   ```bash
   php artisan reverb:start
   ```

2. **Kiểm tra port:**
   - Mặc định: 8080
   - Xem trong `.env`: `REVERB_PORT=8080`

3. **Kiểm tra CORS:**
   - Đảm bảo Reverb cho phép connection từ localhost

4. **Kiểm tra token:**
   - Token phải hợp lệ
   - Token phải có quyền truy cập thread

### Kết nối được nhưng không nhận message

1. **Kiểm tra channel name:**
   - Backend broadcast: `private-chat.thread.{id}`
   - Frontend subscribe: `echo.private('chat.thread.{id}')` ✅

2. **Kiểm tra event name:**
   - Backend: `broadcastAs()` trả về `'message.created'`
   - Frontend: `.listen('.message.created', ...)` ✅ (có dấu `.`)

3. **Kiểm tra authorization:**
   - Mở DevTools → Network
   - Xem request POST `/broadcasting/auth`
   - Status phải là 200

4. **Kiểm tra log:**
   - Xem log panel trong HTML
   - Xem Laravel log: `storage/logs/laravel.log`

### Message gửi được nhưng không hiển thị realtime

1. **Kiểm tra BROADCAST_CONNECTION:**
   ```bash
   php artisan tinker
   >>> config('broadcasting.default')
   ```
   Phải là `'reverb'`

2. **Kiểm tra event có broadcast không:**
   - Xem Laravel log
   - Tìm "New chat message broadcasted"

3. **Kiểm tra WebSocket connection:**
   - Mở DevTools → Network → WS
   - Xem có connection đến Reverb không
   - Xem có message mới khi gửi tin nhắn không

## Debug Tips

### 1. Xem Log trong Browser Console

Mở DevTools → Console, sẽ thấy các log từ Echo:
- Connection events
- Channel subscription
- Received messages

### 2. Xem Network Tab

DevTools → Network:
- Filter: WS (WebSocket)
- Xem connection status
- Xem messages qua WebSocket

### 3. Xem Laravel Log

```bash
tail -f storage/logs/laravel.log
```

Tìm:
- "New chat message broadcasted"
- Broadcasting errors
- Channel authorization

### 4. Test với curl

Test API send message:
```bash
curl -X POST http://localhost:8000/api/chat/threads/1/messages \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"content":"Test message"}'
```

## Cấu Trúc Code

### HTML Test File Structure

```
websocket-test.html
├── Config Panel
│   ├── API URL input
│   ├── Reverb config inputs
│   ├── Auth token input
│   └── Connect/Disconnect buttons
├── Main Content
│   ├── Sidebar (thread list)
│   └── Chat Area
│       ├── Messages container
│       ├── Typing indicator
│       └── Input form
└── Log Panel (debug info)
```

### Key Functions

- `connect()`: Initialize Echo và kết nối
- `disconnect()`: Ngắt kết nối
- `subscribeToChannel()`: Subscribe vào private channel
- `addMessage()`: Thêm message vào UI
- `sendMessage()`: Gửi message qua API
- `showTypingIndicator()`: Hiển thị typing indicator
- `log()`: Ghi log vào log panel

## Next Steps

Sau khi test thành công với HTML file, bạn có thể:

1. **Integrate vào Next.js:**
   - Copy logic từ HTML test
   - Adapt vào React components
   - Sử dụng hooks (useChat, useTyping)

2. **Thêm tính năng:**
   - Load messages khi connect
   - Typing event khi user gõ
   - Read receipts
   - Reactions
   - File upload

3. **Production:**
   - Update environment variables
   - Secure token storage
   - Error handling
   - Reconnection logic

