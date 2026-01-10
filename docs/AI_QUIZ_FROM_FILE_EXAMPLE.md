# AI Quiz Generation from File Upload - Implementation Guide

## Overview
This feature allows users to upload files (PDF, DOCX, TXT) and automatically generate quiz questions using AI.

## API Endpoint

### POST `/api/exams/ai-from-file`

**Request:**
```http
POST /api/exams/ai-from-file
Content-Type: multipart/form-data

file: [PDF/DOCX/TXT file]
number_of_questions: 5 (optional, default: 5, max: 20)
```

**Response (Success):**
```json
{
  "message": "Quiz generated successfully from file",
  "exam": {
    "id": 123,
    "title": "Introduction to Machine Learning",
    "description": "AI generated quiz from uploaded file",
    "category_id": 1,
    "difficulty": "Beginner",
    "duration": 10,
    "passing_score": 70,
    "max_attempts": 3,
    "status": "draft",
    "is_ai_generated": true,
    "questions": [
      {
        "id": 456,
        "content": "What is machine learning?",
        "options": {
          "A": "A type of artificial intelligence",
          "B": "A programming language",
          "C": "A database system",
          "D": "A web framework"
        },
        "answer": "A",
        "explanation": "Machine learning is a subset of artificial intelligence that enables computers to learn without being explicitly programmed.",
        "type": "multiple_choice",
        "points": 1
      }
    ]
  }
}
```

**Response (Error):**
```json
{
  "message": "Failed to generate quiz from file",
  "error": "No readable text found in the uploaded file"
}
```

## File Requirements

- **Supported formats:** PDF, DOCX, DOC, TXT
- **Maximum file size:** 10MB
- **Content limit:** ~4000 characters (automatically truncated)
- **File must contain readable text**

## AI Prompt Used

The system uses this prompt structure to generate quizzes:

```
You are an AI quiz generator. Based on the provided content, create a quiz with exactly {number_of_questions} multiple choice questions.

IMPORTANT: Return ONLY valid JSON, no markdown formatting, no explanations.

Requirements:
- Generate a relevant quiz title based on the content
- Create a brief description (1-2 sentences)
- Generate exactly {number_of_questions} multiple choice questions
- Each question must have 4 options (A, B, C, D)
- Indicate the correct answer by index (0, 1, 2, or 3)
- Provide a short explanation for each correct answer
- Questions should test understanding of key concepts from the content

Return this exact JSON structure:
{
  "title": "Quiz title based on content",
  "description": "Brief description of what this quiz covers",
  "questions": [
    {
      "question": "Question text here?",
      "options": ["Option A", "Option B", "Option C", "Option D"],
      "answer": 0,
      "explanation": "Brief explanation of why this is correct"
    }
  ]
}

Content to analyze:
{extracted_content}
```

## Implementation Details

### 1. File Text Extraction (`FileTextExtractor`)
- **PDF:** Uses `smalot/pdfparser` library
- **DOCX:** Uses `phpoffice/phpword` library  
- **TXT:** Direct file reading
- **Preprocessing:** Removes extra whitespace, unreadable characters, limits to 4000 chars

### 2. AI Service (`AIQuizFromFileService`)
- Calls Gemini AI API with extracted text
- Validates JSON response structure
- Creates quiz and questions in database
- Sets status to 'draft' for admin review

### 3. Controller (`AIQuizController::generateFromFile`)
- Validates file upload
- Handles errors gracefully
- Returns structured JSON response

## Error Handling

The system handles various error scenarios:

1. **File validation errors:** Invalid file type, size too large
2. **Text extraction errors:** Empty files, corrupted files, unreadable content
3. **AI service errors:** API failures, invalid JSON responses
4. **Database errors:** Missing categories, transaction failures

## Usage Examples

### Frontend JavaScript (Fetch API)
```javascript
const formData = new FormData();
formData.append('file', fileInput.files[0]);
formData.append('number_of_questions', 8);

fetch('/api/exams/ai-from-file', {
  method: 'POST',
  body: formData,
  headers: {
    'Authorization': 'Bearer ' + token
  }
})
.then(response => response.json())
.then(data => {
  if (data.exam) {
    console.log('Quiz created:', data.exam);
  } else {
    console.error('Error:', data.error);
  }
});
```

### cURL Example
```bash
curl -X POST \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -F "file=@document.pdf" \
  -F "number_of_questions=5" \
  http://localhost:8000/api/exams/ai-from-file
```

## Configuration Required

Make sure these are set in your `.env` file:
```env
GEMINI_API_URL=https://generativelanguage.googleapis.com/v1beta/models/gemini-pro:generateContent
GEMINI_API_KEY=your_gemini_api_key_here
```

## Database Requirements

- At least one `Category` must exist in the database
- Standard `exams` and `questions` tables as per existing schema
- `is_ai_generated` field should be boolean in `exams` table

## Security Considerations

1. File uploads are validated for type and size
2. Temporary files are handled securely
3. AI responses are validated before database insertion
4. Generated quizzes require admin approval (draft status)
5. Content is limited to prevent excessive API usage

## Performance Notes

- File processing is synchronous (consider queue for large files)
- AI API calls have 60-second timeout
- Content is limited to 4000 characters for optimal AI performance
- Database operations use transactions for consistency