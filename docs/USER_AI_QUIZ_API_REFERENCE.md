# User AI Quiz API Reference

## Tổng Quan
API cho phép người dùng tạo Quiz bằng AI từ file với đầy đủ thông tin như Admin.

## Endpoint

### POST `/api/user/exams/ai-generate-from-file`

Tạo Quiz từ file upload với thông tin đầy đủ do người dùng cung cấp.

**Headers:**
```
Content-Type: multipart/form-data
Authorization: Bearer {token} (nếu cần)
```

**Request Body (Form Data):**

#### File Upload (Bắt buộc)
- `file` (file): File PDF, DOCX, DOC hoặc TXT (tối đa 10MB)
- `number_of_questions` (integer, optional): Số câu hỏi (1-20, mặc định: 5)

#### Thông Tin Quiz (Bắt buộc)
- `title` (string, required): Tiêu đề Quiz (tối đa 255 ký tự)
- `description` (string, optional): Mô tả Quiz
- `category_id` (integer, required): ID danh mục (phải tồn tại)
- `difficulty` (string, required): Độ khó (`Beginner`, `Intermediate`, `Advanced`)
- `duration` (integer, required): Thời gian làm bài (phút, tối thiểu 1)
- `passing_score` (integer, required): Điểm đạt (0-100%)
- `max_attempts` (integer, required): Số lần làm bài tối đa (tối thiểu 1)

#### Thông Tin Bổ Sung (Tùy chọn - AI sẽ tự tạo nếu không cung cấp)
- `color` (string, optional): Màu sắc Quiz (hex code, tối đa 7 ký tự)
- `price_token` (integer, optional): Giá token (0-1,000,000)
- `learning_objectives` (array, optional): Mục tiêu học tập (AI tự tạo nếu trống)
- `prerequisites` (array, optional): Kiến thức tiên quyết (AI tự tạo nếu trống)
- `tags` (array, optional): Thẻ tag (AI tự tạo nếu trống)

**Example Request:**
```javascript
const formData = new FormData();
formData.append('file', fileInput.files[0]);
formData.append('number_of_questions', '10');
formData.append('title', 'Quiz Lập Trình JavaScript');
formData.append('description', 'Quiz kiểm tra kiến thức cơ bản về JavaScript');
formData.append('category_id', '1');
formData.append('difficulty', 'Intermediate');
formData.append('duration', '30');
formData.append('passing_score', '80');
formData.append('max_attempts', '3');
formData.append('color', '#3B82F6');
formData.append('price_token', '100');

// Arrays need to be sent as JSON strings or multiple fields
// Only send if user provided them, otherwise AI will generate
if (learningObjectives.length > 0) {
    formData.append('learning_objectives', JSON.stringify(learningObjectives));
}
if (prerequisites.length > 0) {
    formData.append('prerequisites', JSON.stringify(prerequisites));
}
if (tags.length > 0) {
    formData.append('tags', JSON.stringify(tags));
}

fetch('/api/user/exams/ai-generate-from-file', {
    method: 'POST',
    body: formData,
    headers: {
        'Authorization': 'Bearer ' + token
    }
});
```

**Response Success (201):**
```json
{
    "message": "Quiz được tạo thành công từ file với AI",
    "data": {
        "id": 123,
        "title": "Quiz Lập Trình JavaScript",
        "description": "Quiz kiểm tra kiến thức cơ bản về JavaScript",
        "category_id": 1,
        "difficulty": "Intermediate",
        "duration": 30,
        "color": "#3B82F6",
        "passing_score": 80,
        "max_attempts": 3,
        "learning_objectives": [
            "Hiểu cú pháp JavaScript cơ bản",
            "Sử dụng functions và objects",
            "Áp dụng các khái niệm lập trình hướng đối tượng"
        ],
        "prerequisites": [
            "Kiến thức HTML/CSS cơ bản",
            "Hiểu biết về lập trình cơ bản"
        ],
        "tags": ["javascript", "programming", "web", "frontend"],
        "status": "draft",
        "is_ai_generated": true,
        "price_token": 100,
        "created_at": "2024-01-09T10:30:00.000Z",
        "updated_at": "2024-01-09T10:30:00.000Z",
        "category": {
            "id": 1,
            "name": "Lập Trình",
            "description": "Các khóa học về lập trình"
        },
        "questions": [
            {
                "id": 456,
                "content": "JavaScript là ngôn ngữ gì?",
                "options": {
                    "A": "Ngôn ngữ biên dịch",
                    "B": "Ngôn ngữ thông dịch",
                    "C": "Ngôn ngữ assembly",
                    "D": "Ngôn ngữ máy"
                },
                "answer": "B",
                "explanation": "JavaScript là ngôn ngữ thông dịch, được thực thi trực tiếp bởi trình duyệt hoặc Node.js",
                "type": "multiple_choice",
                "points": 1,
                "pivot": {
                    "exam_id": 123,
                    "question_id": 456,
                    "order": 1
                }
            }
        ]
    }
}
```

**Response Error (422):**
```json
{
    "message": "Không thể tạo quiz từ file",
    "error": "Chi tiết lỗi cụ thể"
}
```

**Response Validation Error (422):**
```json
{
    "message": "The given data was invalid.",
    "errors": {
        "title": ["Tiêu đề Quiz là bắt buộc."],
        "category_id": ["Vui lòng chọn danh mục."],
        "file": ["Chỉ hỗ trợ file PDF, DOCX, DOC và TXT."]
    }
}
```

## Validation Rules

### File Upload
- **file**: Bắt buộc, phải là file hợp lệ
- **Định dạng**: PDF, DOCX, DOC, TXT
- **Kích thước**: Tối đa 10MB
- **number_of_questions**: Số nguyên từ 1-20

### Quiz Information
- **title**: Bắt buộc, chuỗi tối đa 255 ký tự
- **description**: Tùy chọn, chuỗi
- **category_id**: Bắt buộc, phải tồn tại trong bảng categories
- **difficulty**: Bắt buộc, một trong: `Beginner`, `Intermediate`, `Advanced`
- **duration**: Bắt buộc, số nguyên tối thiểu 1
- **passing_score**: Bắt buộc, số nguyên từ 0-100
- **max_attempts**: Bắt buộc, số nguyên tối thiểu 1
- **color**: Tùy chọn, chuỗi tối đa 7 ký tự (hex color)
- **price_token**: Tùy chọn, số nguyên từ 0-1,000,000
- **learning_objectives**: Tùy chọn, mảng
- **prerequisites**: Tùy chọn, mảng
- **tags**: Tùy chọn, mảng

## Luồng Hoạt Động

1. **Upload File**: Người dùng chọn file PDF/DOCX/TXT
2. **Nhập Thông Tin**: Điền thông tin Quiz cơ bản (title, category, duration, v.v.)
3. **Tùy chọn nâng cao**: Có thể để trống learning_objectives, prerequisites, tags
4. **AI Xử Lý**: 
   - Trích xuất text từ file
   - Gọi AI (Gemini) để tạo câu hỏi + metadata
   - AI tự tạo learning_objectives, prerequisites, tags nếu user không cung cấp
   - Parse response thành cấu trúc Quiz hoàn chỉnh
5. **Tạo Database**: 
   - Tạo Exam với thông tin người dùng + AI-generated metadata
   - Tạo Questions từ AI response
   - Liên kết Questions với Exam
   - Lưu giá token nếu có
6. **Trả Response**: Quiz hoàn chỉnh với questions và metadata

## So Sánh với API Cũ

| Tính Năng | API Cũ (`/exams/ai-from-file`) | API Mới (`/user/exams/ai-generate-from-file`) |
|-----------|--------------------------------|-----------------------------------------------|
| File Upload | ✅ | ✅ |
| Số câu hỏi | ✅ | ✅ |
| Tiêu đề Quiz | ❌ (AI tự tạo) | ✅ (Người dùng nhập) |
| Mô tả | ❌ (Mặc định) | ✅ (Người dùng nhập) |
| Danh mục | ❌ (Tự động) | ✅ (Người dùng chọn) |
| Độ khó | ❌ (Mặc định Beginner) | ✅ (Người dùng chọn) |
| Thời gian | ❌ (Tự động tính) | ✅ (Người dùng nhập) |
| Điểm đạt | ❌ (Mặc định 70%) | ✅ (Người dùng nhập) |
| Số lần làm | ❌ (Mặc định 3) | ✅ (Người dùng nhập) |
| Giá token | ❌ | ✅ (Người dùng nhập) |
| Màu sắc | ❌ | ✅ (Người dùng nhập) |
| Mục tiêu học tập | ❌ | ✅ (AI tự tạo hoặc người dùng nhập) |
| Kiến thức tiên quyết | ❌ | ✅ (AI tự tạo hoặc người dùng nhập) |
| Tags | ❌ | ✅ (AI tự tạo hoặc người dùng nhập) |

## Error Handling

### File Errors
- File không hợp lệ hoặc không đọc được
- File quá lớn (>10MB)
- Định dạng file không được hỗ trợ

### AI Errors
- API Gemini không khả dụng
- AI trả về response không hợp lệ
- Không thể parse JSON từ AI

### Database Errors
- Category không tồn tại
- Lỗi khi tạo Exam hoặc Questions
- Lỗi transaction database

### Validation Errors
- Thiếu thông tin bắt buộc
- Định dạng dữ liệu không đúng
- Giá trị ngoài phạm vi cho phép

## Frontend Integration

### Form Structure
```html
<form enctype="multipart/form-data">
    <!-- File Upload -->
    <input type="file" name="file" accept=".pdf,.docx,.doc,.txt" required>
    <input type="number" name="number_of_questions" min="1" max="20" value="5">
    
    <!-- Quiz Info -->
    <input type="text" name="title" required maxlength="255">
    <textarea name="description"></textarea>
    <select name="category_id" required>
        <!-- Load from /api/categories -->
    </select>
    <select name="difficulty" required>
        <option value="Beginner">Beginner</option>
        <option value="Intermediate">Intermediate</option>
        <option value="Advanced">Advanced</option>
    </select>
    <input type="number" name="duration" required min="1">
    <input type="number" name="passing_score" required min="0" max="100">
    <input type="number" name="max_attempts" required min="1">
    
    <!-- Optional -->
    <input type="color" name="color">
    <input type="number" name="price_token" min="0" max="1000000">
    <!-- Arrays for learning_objectives, prerequisites, tags -->
</form>
```

### JavaScript Example
```javascript
async function createAIQuiz(formData) {
    try {
        const response = await fetch('/api/user/exams/ai-generate-from-file', {
            method: 'POST',
            body: formData,
            headers: {
                'Authorization': `Bearer ${token}`
            }
        });
        
        if (!response.ok) {
            const error = await response.json();
            throw new Error(error.message);
        }
        
        const result = await response.json();
        console.log('Quiz created:', result.data);
        return result.data;
        
    } catch (error) {
        console.error('Error creating quiz:', error);
        throw error;
    }
}
```

---

**Lợi ích của API mới:**
- Người dùng có toàn quyền kiểm soát thông tin Quiz
- Tích hợp tốt với hệ thống token pricing
- Tương thích với workflow Admin hiện có
- Validation đầy đủ đảm bảo chất lượng dữ liệu
- Hỗ trợ tất cả tính năng nâng cao của Quiz