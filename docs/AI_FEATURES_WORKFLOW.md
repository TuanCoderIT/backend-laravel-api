# AI Features Workflow - Tóm Tắt Luồng Hoạt Động

## 🧠 1. AI Quiz Generation từ File Upload

### **API Endpoint**
```
POST /api/exams/ai-from-file
Content-Type: multipart/form-data
```

### **Luồng Hoạt Động**
```
File Upload → Text Extraction → AI Processing → Quiz Creation → Database Save
```

### **Chi Tiết Từng Bước**

#### **Step 1: File Upload & Validation**
- User upload file (PDF, DOCX, TXT)
- Validate: file type, size (max 10MB)
- Check file có readable text không

#### **Step 2: Text Extraction**
```php
FileTextExtractor::extract($file)
├── PDF: smalot/pdfparser
├── DOCX: phpoffice/phpword  
└── TXT: file_get_contents()
```
- Extract text từ file
- Clean & limit content (~4000 chars)
- Remove special characters

#### **Step 3: AI Processing**
```php
AIQuizFromFileService::generateQuiz($text, $numberOfQuestions)
```
- Build prompt với extracted text
- Call Gemini API với structured prompt
- Parse JSON response từ AI

#### **Step 4: Quiz Creation**
- Validate AI response structure
- Create Exam record (status: 'draft')
- Create Questions với options & answers
- Set `is_ai_generated = true`

#### **Step 5: Response**
```json
{
  "message": "Quiz generated successfully",
  "exam": {
    "id": 123,
    "title": "AI Generated Title",
    "questions": [...]
  }
}
```

### **AI Prompt Structure**
```
You are a quiz generator. Create {N} questions from this content:
{extracted_text}

Return JSON:
{
  "title": "...",
  "description": "...", 
  "questions": [
    {
      "question": "...",
      "options": ["A", "B", "C", "D"],
      "answer": 0,
      "explanation": "..."
    }
  ]
}
```

---

## 💬 2. AI Study Assistant Chatbot (Advanced Architecture)

### **API Endpoint**
```
POST /api/chat/ai-assistant
Authorization: Bearer {token}
```

### **Luồng Hoạt Động (Multi-Service Architecture)**
```
User Question → Intent Analysis → Action Routing → Data Processing → Response Formatting → Final Response
```

### **Chi Tiết Architecture**

#### **Step 1: Input Processing (AIChatController)**
```json
{
  "message": "Tìm khóa học về lập trình",
  "context_type": "course",  // optional
  "context_id": 123          // optional
}
```
- Validate input với AIChatRequest
- Forward đến AIChatService

#### **Step 2: Intent Analysis (AIRouterService)**
```php
AIRouterService::analyzeIntent($message, $contextType, $contextId)
```
**AI Router Prompt:**
- Phân tích intent của user
- Classify vào 8 actions: course_info, course_search, exam_info, exam_search, learning_progress, study_recommendation, general_chat, off_topic
- Return JSON với action và params

**Available Actions:**
- `course_info` - Hỏi về khóa học cụ thể
- `course_search` - Tìm kiếm khóa học
- `exam_info` - Hỏi về đề thi cụ thể  
- `exam_search` - Tìm kiếm đề thi
- `learning_progress` - Xem tiến độ học tập
- `study_recommendation` - Gợi ý học tập
- `general_chat` - Câu hỏi giáo dục chung
- `off_topic` - Câu hỏi ngoài phạm vi

#### **Step 3: Action Processing (AIActionHandlerService)**
```php
AIActionHandlerService::handleAction($action, $params, $userId)
```

**Database Queries theo Action:**
- **course_search**: `Course::with('category')->where('title', 'like', "%{query}%")`
- **exam_search**: `Exam::with('category')->where('title', 'like', "%{query}%")`
- **learning_progress**: `CourseProgress::where('user_id', $userId)`
- **study_recommendation**: `Course::where('difficulty', $level)`

**Return structured data:**
```json
{
  "success": true,
  "data": { "courses": [...] },
  "message": "Tìm thấy 5 khóa học"
}
```

#### **Step 4: Response Formatting (AIFormatterService)**
```php
AIFormatterService::formatResponse($action, $data, $originalMessage)
```

**2 Formatting Modes:**
1. **Structured Data** (course_search, exam_search, etc.):
   - Format thành text đẹp với emoji
   - Không cần gọi AI API
   
2. **General Chat** (general_chat):
   - Gọi Gemini API để trả lời tự nhiên
   - Educational prompt với context

#### **Step 5: Final Response**
```json
{
  "message": "Trả lời thành công",
  "data": {
    "response": "🔍 Tìm thấy 3 khóa học:\n📚 **Lập trình Python**...",
    "action": "course_search",
    "context_used": false
  }
}
```

---

## 🔄 So Sánh 2 Tính Năng

| Aspect | Quiz Generation | Chatbot |
|--------|----------------|---------|
| **Input** | File upload | Text message |
| **Processing** | File → Text → Quiz | Message → Intent → Action → Format |
| **AI Calls** | 1 call (generation) | 1-2 calls (routing + formatting) |
| **Services** | 2 services | 5 services |
| **Complexity** | Simple linear flow | Complex multi-service architecture |
| **AI Task** | Structured generation | Intent analysis + conversation |
| **Output** | Database records | Formatted text response |
| **Context** | File content | Database data |
| **Use Case** | One-time creation | Interactive Q&A |

---

## 🏗️ Architecture Comparison

### **Quiz Generation (Simple)**
```
Controller → Service → AI API → Database
```
- Linear workflow
- Single responsibility per service
- File processing focus

### **Chatbot (Complex)**
```
Controller → Orchestrator → Router → ActionHandler → Formatter → Response
```
- Multi-service architecture
- Separation of concerns
- Intent-based routing
- Flexible response formatting

---

## 🛠️ Technical Architecture

### **Shared Components**
- **Gemini API**: Google AI service
- **Error Handling**: Try-catch với logging
- **Validation**: Request validation classes
- **Response Format**: Consistent JSON structure

### **File Structure**
```
app/
├── Http/Controllers/Api/
│   ├── AIQuizController.php         # Quiz generation
│   └── AIChatController.php         # Chatbot entry point
├── Services/
│   ├── AIQuizFromFileService.php    # Quiz logic
│   ├── AIChatService.php            # Chatbot orchestrator
│   ├── AIRouterService.php          # Intent analysis & routing
│   ├── AIActionHandlerService.php   # Database operations per action
│   ├── AIFormatterService.php       # Response formatting
│   └── FileTextExtractor.php        # File processing
└── Http/Requests/
    ├── AIQuizFromFileRequest.php    # Quiz validation
    └── AIChatRequest.php            # Chat validation
```

### **Service Responsibilities**

#### **AIRouterService**
- **Purpose**: Phân tích intent của user message
- **Input**: User message + context
- **Output**: Action type + parameters
- **AI Usage**: Gemini API để classify intent
- **Fallback**: Keyword-based classification nếu AI fail

#### **AIActionHandlerService**  
- **Purpose**: Xử lý database operations cho từng action
- **Input**: Action type + params + user_id
- **Output**: Structured data từ database
- **No AI**: Pure database queries và business logic

#### **AIFormatterService**
- **Purpose**: Format response thành text đẹp cho user
- **Input**: Action + data + original message  
- **Output**: Formatted text response
- **AI Usage**: Chỉ cho general_chat, còn lại format template

#### **AIChatService (Orchestrator)**
- **Purpose**: Điều phối toàn bộ flow
- **Workflow**: Router → ActionHandler → Formatter
- **Error Handling**: Centralized error management

### **Database Impact**
**Quiz Generation:**
- Creates: `exams`, `questions` records
- Marks: `is_ai_generated = true`

**Chatbot:**
- Reads: `courses`, `exams`, `chapters`, `lessons`
- No database writes (stateless)

---

## 🎯 Key Differences

### **Quiz Generation**
- **Purpose**: Content creation
- **Approach**: File processing + structured generation
- **Result**: Persistent data (quiz in database)
- **User Flow**: Upload → Wait → Get quiz

### **Chatbot**
- **Purpose**: Interactive assistance  
- **Approach**: Context injection + conversational AI
- **Result**: Temporary response (no persistence)
- **User Flow**: Ask → Get answer → Continue conversation

---

## 📊 Performance & Limitations

### **Quiz Generation**
- **Time**: 10-30 seconds (file processing + AI)
- **Limit**: 10MB files, 20 questions max
- **Cost**: 1 API call per generation

### **Chatbot**
- **Time**: 2-5 seconds per response
- **Limit**: 1000 chars per message
- **Cost**: 1 API call per question

---

## 🚀 Usage Examples

### **Quiz Generation**
```bash
curl -X POST /api/exams/ai-from-file \
  -F "file=@document.pdf" \
  -F "number_of_questions=5"
```

### **Chatbot**
```bash
curl -X POST /api/chat/ai-assistant \
  -H "Authorization: Bearer token" \
  -d '{"message": "Explain AI", "context_type": "course", "context_id": 1}'
```

---

**Cả 2 tính năng đều sử dụng Gemini AI nhưng phục vụ mục đích khác nhau: Quiz Generation tạo nội dung, Chatbot hỗ trợ tương tác học tập.**

---

## 🤖 AI API Usage Patterns

### **Quiz Generation**
```php
// Single AI call với structured prompt
$prompt = "Create quiz from: {content}. Return JSON: {...}";
$response = Gemini::generate($prompt);
$quiz = json_decode($response);
```

### **Chatbot - Intent Analysis**
```php
// AI Router call
$prompt = "Analyze intent: {message}. Return action JSON.";
$intent = Gemini::analyze($prompt);
```

### **Chatbot - General Chat**
```php  
// AI Formatter call (chỉ khi action = general_chat)
$prompt = "Answer educational question: {message}";
$answer = Gemini::chat($prompt);
```

### **API Call Optimization**
- **Quiz**: 1 call per generation (expensive but one-time)
- **Chatbot**: 1-2 calls per message (cheaper, frequent)
- **Fallback**: Keyword matching khi AI fail
- **Caching**: Có thể cache intent patterns

---

## 💡 Key Insights

### **Design Philosophy**
- **Quiz Generation**: Simple, focused, reliable
- **Chatbot**: Flexible, intelligent, scalable

### **When to Use Each Approach**
- **Simple AI tasks**: Follow Quiz Generation pattern
- **Complex AI interactions**: Follow Chatbot pattern

### **Scalability**
- **Quiz**: Scales với file processing capacity
- **Chatbot**: Scales với intent complexity và database size