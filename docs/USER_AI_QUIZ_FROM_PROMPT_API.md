# User AI Quiz from Prompt API Reference

## Tổng Quan
API cho phép người dùng tạo Quiz bằng AI từ text prompt với đầy đủ thông tin như Admin.

## Endpoint

### POST `/api/user/exams/ai-generate-from-prompt`

Tạo Quiz từ text prompt với thông tin đầy đủ do người dùng cung cấp.

**Headers:**
```
Content-Type: application/json
Authorization: Bearer {token} (nếu cần)
```

**Request Body (JSON):**

#### AI Prompt (Bắt buộc)
- `prompt` (string, required): Text prompt để AI tạo quiz (10-2000 ký tự)
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
```json
{
  "prompt": "Tạo quiz về lập trình JavaScript cơ bản, bao gồm biến, hàm, và vòng lặp",
  "number_of_questions": 8,
  "title": "Quiz JavaScript Cơ Bản",
  "description": "Kiểm tra kiến thức cơ bản về JavaScript",
  "category_id": 1,
  "difficulty": "Beginner",
  "duration": 25,
  "passing_score": 75,
  "max_attempts": 3,
  "color": "#F59E0B",
  "price_token": 50
}
```

**Response Success (201):**
```json
{
    "message": "Quiz được tạo thành công từ prompt với AI",
    "data": {
        "id": 124,
        "title": "Quiz JavaScript Cơ Bản",
        "description": "Kiểm tra kiến thức cơ bản về JavaScript",
        "category_id": 1,
        "difficulty": "Beginner",
        "duration": 25,
        "color": "#F59E0B",
        "passing_score": 75,
        "max_attempts": 3,
        "learning_objectives": [
            "Học viên sẽ hiểu được cách khai báo biến trong JavaScript",
            "Học viên có thể tạo và sử dụng functions",
            "Học viên phân tích được các loại vòng lặp",
            "Học viên áp dụng được cú pháp JavaScript cơ bản"
        ],
        "prerequisites": [
            "Kiến thức HTML/CSS cơ bản",
            "Hiểu biết về lập trình cơ bản",
            "Làm quen với text editor"
        ],
        "tags": ["javascript", "programming", "variables", "functions", "loops", "beginner"],
        "status": "draft",
        "is_ai_generated": true,
        "price_token": 50,
        "created_at": "2024-01-09T11:00:00.000Z",
        "updated_at": "2024-01-09T11:00:00.000Z",
        "category": {
            "id": 1,
            "name": "Lập Trình",
            "description": "Các khóa học về lập trình"
        },
        "questions": [
            {
                "id": 457,
                "content": "Cách nào đúng để khai báo biến trong JavaScript?",
                "options": {
                    "A": "var myVar = 10;",
                    "B": "variable myVar = 10;",
                    "C": "int myVar = 10;",
                    "D": "declare myVar = 10;"
                },
                "answer": "A",
                "explanation": "Trong JavaScript, từ khóa 'var', 'let', hoặc 'const' được sử dụng để khai báo biến",
                "type": "multiple_choice",
                "points": 1,
                "pivot": {
                    "exam_id": 124,
                    "question_id": 457,
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
    "message": "Không thể tạo quiz từ prompt",
    "error": "Chi tiết lỗi cụ thể"
}
```

**Response Validation Error (422):**
```json
{
    "message": "The given data was invalid.",
    "errors": {
        "prompt": ["Vui lòng nhập prompt để tạo quiz."],
        "title": ["Tiêu đề Quiz là bắt buộc."],
        "category_id": ["Vui lòng chọn danh mục."]
    }
}
```

## Validation Rules

### AI Prompt
- **prompt**: Bắt buộc, chuỗi từ 10-2000 ký tự
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

1. **Nhập Prompt**: Người dùng nhập text prompt mô tả quiz cần tạo
2. **Nhập Thông Tin**: Điền thông tin Quiz cơ bản (title, category, duration, v.v.)
3. **Tùy chọn nâng cao**: Có thể để trống learning_objectives, prerequisites, tags
4. **AI Xử Lý**: 
   - Phân tích prompt của người dùng
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

| Tính Năng | API Cũ (`/exams/ai-generate`) | API Mới (`/user/exams/ai-generate-from-prompt`) |
|-----------|--------------------------------|--------------------------------------------------|
| Text Prompt | ✅ | ✅ |
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

## Example Prompts

### Prompt Examples:
```
"Tạo quiz về React Hooks, bao gồm useState, useEffect, và custom hooks"

"Quiz kiểm tra kiến thức về cơ sở dữ liệu MySQL: SELECT, JOIN, và INDEX"

"Tạo bài kiểm tra về marketing digital: SEO, SEM, và social media marketing"

"Quiz về an toàn thông tin: mã hóa, firewall, và phishing"

"Kiểm tra kiến thức tiếng Anh giao tiếp: present tense, past tense, và future tense"
```

## Frontend Integration

### Form Structure
```html
<form>
    <!-- AI Prompt -->
    <textarea name="prompt" required minlength="10" maxlength="2000" 
              placeholder="Mô tả quiz bạn muốn tạo..."></textarea>
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
    <!-- Arrays for learning_objectives, prerequisites, tags (optional) -->
</form>
```

### JavaScript Example
```javascript
async function createAIQuizFromPrompt(formData) {
    try {
        const response = await fetch('/api/user/exams/ai-generate-from-prompt', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Authorization': `Bearer ${token}`
            },
            body: JSON.stringify(formData)
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

// Usage
const quizData = {
    prompt: "Tạo quiz về JavaScript ES6: arrow functions, destructuring, và modules",
    number_of_questions: 10,
    title: "Quiz JavaScript ES6",
    description: "Kiểm tra kiến thức về tính năng mới của ES6",
    category_id: 1,
    difficulty: "Intermediate",
    duration: 30,
    passing_score: 80,
    max_attempts: 2,
    color: "#10B981",
    price_token: 100
};

createAIQuizFromPrompt(quizData);
```

---

**Lợi ích của API mới:**
- Người dùng có toàn quyền kiểm soát thông tin Quiz
- AI tự động tạo metadata phù hợp với prompt
- Tích hợp tốt với hệ thống token pricing
- Validation đầy đủ đảm bảo chất lượng dữ liệu
- Linh hoạt: user có thể tự nhập hoặc để AI tạo metadata