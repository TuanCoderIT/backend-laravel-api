# Fix Broadcasting Authentication với Bearer Token

## Vấn đề
- POST `/broadcasting/auth` trả về 403 Forbidden
- Backend dùng Laravel Sanctum với Bearer token (không dùng cookie)
- Frontend Next.js dùng Echo + pusher-js

## Giải pháp đã áp dụng

### 1. Sửa `config/broadcasting.php`

```php
'reverb' => [
    'driver' => 'reverb',
    // ... other config
    'middleware' => [
        'auth:api',
    ],
],

'auth' => [
    'guard' => 'api',
    'middleware' => [
        'auth:api',
    ],
],
```

### 2. Sửa `routes/channels.php`

```php
Broadcast::channel('chat.thread.{threadId}', function ($user, $threadId) {
    return ChatParticipant::where('thread_id', $threadId)
        ->where('user_id', $user->id)
        ->exists();
}, ['guards' => ['api']]);
```

### 3. Thêm route override trong `routes/api.php`

```php
Route::post('broadcasting/auth', function () {
    return app(\Illuminate\Broadcasting\BroadcastController::class)->authenticate(request());
})->middleware('auth:api');
```

**Lưu ý**: Route này phải được đặt TRƯỚC middleware group `auth:sanctum` để override route tự động.

### 4. Thêm route override trong `routes/web.php`

```php
Route::post('/broadcasting/auth', function () {
    return app(\Illuminate\Broadcasting\BroadcastController::class)->authenticate(request());
})->middleware('auth:api');
```

**Lưu ý**: Route này override route tự động từ `withBroadcasting()` và sử dụng middleware `auth:api`.

### 5. Cập nhật Echo config trong Frontend

Đảm bảo `authEndpoint` trỏ đúng path (KHÔNG có `/api`):

```typescript
const echo = new Echo({
  broadcaster: 'reverb',
  // ... other config
  authEndpoint: 'http://localhost:8000/broadcasting/auth', // KHÔNG có /api
  auth: {
    headers: {
      Authorization: `Bearer ${token}`,
    },
  },
});
```

**Lưu ý**: 
- Route trong `routes/web.php` không có prefix `/api`
- Endpoint cuối cùng sẽ là: `http://localhost:8000/broadcasting/auth`
- Nếu dùng environment variable, đảm bảo không có `/api` trong path:
  ```typescript
  authEndpoint: `${process.env.NEXT_PUBLIC_BASE_URL || 'http://localhost:8000'}/broadcasting/auth`
  ```

## Các lệnh cần chạy sau khi sửa

```bash
# Clear config cache
php artisan config:clear

# Clear route cache
php artisan route:clear

# Clear application cache
php artisan cache:clear

# Rebuild config cache (optional, cho production)
php artisan config:cache
```

## Kiểm tra

1. **Kiểm tra route đã được tạo:**
```bash
php artisan route:list | grep broadcasting
```

Kết quả mong đợi:
```
POST api/broadcasting/auth ... auth:api
```

2. **Test với curl:**
```bash
curl -X POST http://localhost:8000/api/broadcasting/auth \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"socket_id":"123.456","channel_name":"private-chat.thread.1"}'
```

3. **Kiểm tra trong browser console:**
- Mở DevTools → Network tab
- Subscribe channel trong Echo
- Xem request POST `/api/broadcasting/auth`
- Status phải là 200 (không phải 403)

## Troubleshooting

### Vẫn bị 403
1. Kiểm tra token có hợp lệ không:
```bash
php artisan tinker
>>> $user = \App\Models\User::first();
>>> $token = $user->createToken('test')->plainTextToken;
>>> echo $token;
```

2. Kiểm tra guard 'api' có đúng không trong `config/auth.php`:
```php
'guards' => [
    'api' => [
        'driver' => 'sanctum',
        'provider' => 'users',
    ],
],
```

3. Kiểm tra route có được load không:
```bash
php artisan route:list | grep broadcasting
```

### Token không được nhận
- Đảm bảo Echo config có `auth.headers.Authorization`
- Đảm bảo token được lấy từ storage đúng cách
- Kiểm tra token format: `Bearer {token}` (có space sau Bearer)

### Route không match
- Đảm bảo route trong `routes/api.php` được đặt TRƯỚC middleware group
- Clear route cache: `php artisan route:clear`

