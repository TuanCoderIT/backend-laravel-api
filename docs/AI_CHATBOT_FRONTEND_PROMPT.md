# AI Study Assistant Chatbot - Frontend Implementation

## Yêu Cầu Tổng Quan
Tạo giao diện chat với AI Study Assistant, tích hợp vào hệ thống chat hiện có. AI sẽ trả lời câu hỏi học tập dựa trên dữ liệu courses/exams trong database.

## API Endpoint

### POST `/api/chat/ai-assistant`
**Headers:**
```
Authorization: Bearer {token}
Content-Type: application/json
```

**Request Body:**
```json
{
  "message": "Giải thích machine learning là gì?",
  "context_type": "course",  // optional: "course", "exam", "general" 
  "context_id": 123          // optional: course_id hoặc exam_id
}
```

**Response Success (200):**
```json
{
  "message": "Trả lời thành công",
  "data": {
    "response": "Machine learning là một nhánh của trí tuệ nhân tạo...",
    "context_used": true,
    "timestamp": "2024-01-09T10:30:00.000Z"
  }
}
```

**Response Error (500):**
```json
{
  "message": "Có lỗi xảy ra khi xử lý yêu cầu",
  "error": "Chi tiết lỗi"
}
```

## Luồng Hoạt Động

### 1. Tích Hợp Vào Chat Sidebar
- Thêm "🤖 AI Assistant" vào danh sách chat threads
- Hiển thị như một thread đặc biệt (không phải user chat)
- Icon robot để phân biệt với chat thường

### 2. Chat Interface
- **Input**: Text input để gửi câu hỏi
- **Context Selector**: Dropdown để chọn context (optional)
  - "General" (mặc định)
  - "Course: [Tên khóa học]" (nếu đang xem course)
  - "Exam: [Tên đề thi]" (nếu đang xem exam)
- **Send Button**: Gửi câu hỏi

### 3. Message Display
- **User message**: Hiển thị bên phải như chat thường
- **AI response**: Hiển thị bên trái với:
  - Avatar robot 🤖
  - Tên "AI Assistant"
  - Nội dung trả lời
  - Badge nhỏ nếu có context: "📚 Course" hoặc "📝 Exam"
  - Timestamp

### 4. Context Auto-Detection
- Khi user đang xem course detail → auto set context_type="course", context_id={courseId}
- Khi user đang xem exam detail → auto set context_type="exam", context_id={examId}
- Các trang khác → context_type="general"

### 5. Loading States
- Hiển thị typing indicator khi AI đang xử lý
- Disable input trong lúc chờ response

### 6. Error Handling
- Hiển thị message lỗi nếu API fail
- Retry button cho user thử lại

## Technical Requirements

### State Management
```typescript
interface AIChatState {
  messages: AIChatMessage[];
  isLoading: boolean;
  currentContext: {
    type: 'general' | 'course' | 'exam';
    id?: number;
    name?: string;
  };
}

interface AIChatMessage {
  id: string;
  type: 'user' | 'ai';
  content: string;
  timestamp: string;
  contextUsed?: boolean;
}
```

### API Integration
- Sử dụng axios/fetch để call API
- Handle loading states
- Handle errors gracefully
- Debounce input nếu cần

### Context Detection Logic
```typescript
// Detect context từ current route
const detectContext = () => {
  if (router.pathname.includes('/courses/[slug]')) {
    return { type: 'course', id: courseId, name: courseTitle };
  }
  if (router.pathname.includes('/exams/[id]')) {
    return { type: 'exam', id: examId, name: examTitle };
  }
  return { type: 'general' };
};
```

## Integration Points

### 1. Chat Sidebar
- Thêm AI Assistant thread vào top của thread list
- Unread count = 0 (không cần track unread cho AI)
- Click vào sẽ mở AI chat interface

### 2. Course/Exam Pages
- Thêm floating button "💬 Ask AI" 
- Click sẽ mở AI chat với context tự động

### 3. Existing Chat System
- Reuse ChatMessage components
- Reuse chat layout/styling
- Separate AI messages từ regular chat messages

## User Experience

### Conversation Flow
1. User click "AI Assistant" trong sidebar
2. Hiển thị welcome message: "Xin chào! Tôi là trợ lý học tập AI. Bạn có câu hỏi gì về khóa học không?"
3. User gõ câu hỏi và gửi
4. Hiển thị loading indicator
5. AI response hiển thị với animation
6. User có thể tiếp tục hỏi

### Context Awareness
- Khi ở course page: "Tôi đang xem khóa học '[Tên]'. Hãy hỏi tôi về nội dung này!"
- Khi ở exam page: "Tôi đang xem đề thi '[Tên]'. Có câu hỏi gì về đề thi này không?"
- General: "Hãy hỏi tôi bất cứ điều gì về học tập!"

## Performance Notes
- Messages chỉ lưu trong memory (không persist)
- Clear messages khi refresh page
- Limit message history (max 50 messages)
- Debounce typing để tránh spam API

## Security Notes
- Validate input trước khi gửi API
- Sanitize AI response trước khi hiển thị
- Rate limiting ở frontend (max 10 messages/phút)

---

**Mục tiêu**: Tạo trải nghiệm chat với AI mượt mà, tích hợp tự nhiên vào hệ thống hiện có, giúp học viên có thể hỏi đáp về nội dung học tập một cách dễ dàng.