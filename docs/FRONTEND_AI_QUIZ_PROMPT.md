# Frontend Implementation Guide: AI Quiz Generation Feature

## 🎯 Overview
Implement AI Quiz Generation feature với 2 phương thức:
1. **Text Prompt** - Tạo quiz từ câu lệnh văn bản
2. **File Upload** - Tạo quiz từ file PDF/DOCX/TXT

## 📋 Requirements

### UI Components Cần Tạo:
1. **Quiz Generation Form** với 2 tabs/modes
2. **File Upload Component** với drag & drop
3. **Loading States** cho AI processing
4. **Success/Error Handling** với toast notifications
5. **Quiz Preview** component để xem kết quả

## 🔌 API Endpoints

### 1. Text Prompt Generation
```typescript
// POST /api/exams/ai-generate
interface TextPromptRequest {
  prompt: string;                    // Required: Câu lệnh tạo quiz
  number_of_questions?: number;      // Optional: 1-50, default: 5
}

interface QuizResponse {
  id: number;
  title: string;
  description: string;
  category_id: number;
  difficulty: string;
  duration: number;
  status: 'draft';
  is_ai_generated: true;
  questions: Question[];
}
```

### 2. File Upload Generation
```typescript
// POST /api/exams/ai-from-file
interface FileUploadRequest {
  file: File;                       // Required: PDF/DOCX/TXT, max 10MB
  number_of_questions?: number;     // Optional: 1-20, default: 5
}

// Response giống TextPromptRequest
```

## 💻 Implementation Guide

### 1. Main Quiz Generation Component

```tsx
'use client';

import { useState } from 'react';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { TextPromptForm } from './TextPromptForm';
import { FileUploadForm } from './FileUploadForm';
import { QuizPreview } from './QuizPreview';

export default function AIQuizGenerator() {
  const [generatedQuiz, setGeneratedQuiz] = useState(null);
  const [isLoading, setIsLoading] = useState(false);

  return (
    <div className="max-w-4xl mx-auto p-6">
      <div className="mb-8">
        <h1 className="text-3xl font-bold">Tạo Quiz bằng AI</h1>
        <p className="text-gray-600 mt-2">
          Tạo quiz tự động từ văn bản hoặc tải file lên
        </p>
      </div>

      {!generatedQuiz ? (
        <Tabs defaultValue="text" className="w-full">
          <TabsList className="grid w-full grid-cols-2">
            <TabsTrigger value="text">Từ Văn Bản</TabsTrigger>
            <TabsTrigger value="file">Từ File</TabsTrigger>
          </TabsList>
          
          <TabsContent value="text">
            <TextPromptForm 
              onSuccess={setGeneratedQuiz}
              isLoading={isLoading}
              setIsLoading={setIsLoading}
            />
          </TabsContent>
          
          <TabsContent value="file">
            <FileUploadForm 
              onSuccess={setGeneratedQuiz}
              isLoading={isLoading}
              setIsLoading={setIsLoading}
            />
          </TabsContent>
        </Tabs>
      ) : (
        <QuizPreview 
          quiz={generatedQuiz} 
          onBack={() => setGeneratedQuiz(null)}
        />
      )}
    </div>
  );
}
```

### 2. Text Prompt Form Component

```tsx
'use client';

import { useState } from 'react';
import { Button } from '@/components/ui/button';
import { Textarea } from '@/components/ui/textarea';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Loader2 } from 'lucide-react';
import { toast } from 'sonner';

interface TextPromptFormProps {
  onSuccess: (quiz: any) => void;
  isLoading: boolean;
  setIsLoading: (loading: boolean) => void;
}

export function TextPromptForm({ onSuccess, isLoading, setIsLoading }: TextPromptFormProps) {
  const [prompt, setPrompt] = useState('');
  const [numberOfQuestions, setNumberOfQuestions] = useState(5);

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    
    if (!prompt.trim()) {
      toast.error('Vui lòng nhập nội dung để tạo quiz');
      return;
    }

    setIsLoading(true);
    
    try {
      const response = await fetch('/api/exams/ai-generate', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'Authorization': `Bearer ${getAuthToken()}`, // Implement getAuthToken()
        },
        body: JSON.stringify({
          prompt: prompt.trim(),
          number_of_questions: numberOfQuestions,
        }),
      });

      const data = await response.json();

      if (!response.ok) {
        throw new Error(data.message || 'Có lỗi xảy ra khi tạo quiz');
      }

      toast.success('Tạo quiz thành công!');
      onSuccess(data);
      
    } catch (error) {
      console.error('Error generating quiz:', error);
      toast.error(error.message || 'Không thể tạo quiz. Vui lòng thử lại.');
    } finally {
      setIsLoading(false);
    }
  };

  return (
    <Card>
      <CardHeader>
        <CardTitle>Tạo Quiz từ Văn Bản</CardTitle>
      </CardHeader>
      <CardContent>
        <form onSubmit={handleSubmit} className="space-y-4">
          <div>
            <Label htmlFor="prompt">Nội dung tạo quiz *</Label>
            <Textarea
              id="prompt"
              placeholder="Ví dụ: Tạo quiz về lịch sử Việt Nam thời kỳ phong kiến..."
              value={prompt}
              onChange={(e) => setPrompt(e.target.value)}
              rows={6}
              className="mt-1"
              disabled={isLoading}
            />
            <p className="text-sm text-gray-500 mt-1">
              Mô tả chi tiết chủ đề bạn muốn tạo quiz
            </p>
          </div>

          <div>
            <Label htmlFor="questions">Số câu hỏi</Label>
            <Input
              id="questions"
              type="number"
              min="1"
              max="50"
              value={numberOfQuestions}
              onChange={(e) => setNumberOfQuestions(parseInt(e.target.value) || 5)}
              className="mt-1 w-32"
              disabled={isLoading}
            />
            <p className="text-sm text-gray-500 mt-1">
              Từ 1 đến 50 câu hỏi
            </p>
          </div>

          <Button 
            type="submit" 
            disabled={isLoading || !prompt.trim()}
            className="w-full"
          >
            {isLoading ? (
              <>
                <Loader2 className="mr-2 h-4 w-4 animate-spin" />
                Đang tạo quiz...
              </>
            ) : (
              'Tạo Quiz'
            )}
          </Button>
        </form>
      </CardContent>
    </Card>
  );
}
```

### 3. File Upload Form Component

```tsx
'use client';

import { useState, useCallback } from 'react';
import { useDropzone } from 'react-dropzone';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Upload, File, X, Loader2 } from 'lucide-react';
import { toast } from 'sonner';

interface FileUploadFormProps {
  onSuccess: (quiz: any) => void;
  isLoading: boolean;
  setIsLoading: (loading: boolean) => void;
}

export function FileUploadForm({ onSuccess, isLoading, setIsLoading }: FileUploadFormProps) {
  const [selectedFile, setSelectedFile] = useState<File | null>(null);
  const [numberOfQuestions, setNumberOfQuestions] = useState(5);

  const onDrop = useCallback((acceptedFiles: File[]) => {
    const file = acceptedFiles[0];
    if (file) {
      // Validate file size (10MB)
      if (file.size > 10 * 1024 * 1024) {
        toast.error('File không được vượt quá 10MB');
        return;
      }
      
      // Validate file type
      const allowedTypes = [
        'application/pdf',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'application/msword',
        'text/plain'
      ];
      
      if (!allowedTypes.includes(file.type)) {
        toast.error('Chỉ hỗ trợ file PDF, DOCX, DOC, TXT');
        return;
      }
      
      setSelectedFile(file);
    }
  }, []);

  const { getRootProps, getInputProps, isDragActive } = useDropzone({
    onDrop,
    accept: {
      'application/pdf': ['.pdf'],
      'application/vnd.openxmlformats-officedocument.wordprocessingml.document': ['.docx'],
      'application/msword': ['.doc'],
      'text/plain': ['.txt']
    },
    multiple: false,
    disabled: isLoading
  });

  const removeFile = () => {
    setSelectedFile(null);
  };

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    
    if (!selectedFile) {
      toast.error('Vui lòng chọn file để tạo quiz');
      return;
    }

    setIsLoading(true);
    
    try {
      const formData = new FormData();
      formData.append('file', selectedFile);
      formData.append('number_of_questions', numberOfQuestions.toString());

      const response = await fetch('/api/exams/ai-from-file', {
        method: 'POST',
        headers: {
          'Authorization': `Bearer ${getAuthToken()}`, // Implement getAuthToken()
        },
        body: formData,
      });

      const data = await response.json();

      if (!response.ok) {
        throw new Error(data.error || data.message || 'Có lỗi xảy ra khi tạo quiz');
      }

      toast.success('Tạo quiz từ file thành công!');
      onSuccess(data.exam);
      
    } catch (error) {
      console.error('Error generating quiz from file:', error);
      toast.error(error.message || 'Không thể tạo quiz từ file. Vui lòng thử lại.');
    } finally {
      setIsLoading(false);
    }
  };

  return (
    <Card>
      <CardHeader>
        <CardTitle>Tạo Quiz từ File</CardTitle>
      </CardHeader>
      <CardContent>
        <form onSubmit={handleSubmit} className="space-y-4">
          {/* File Upload Area */}
          <div>
            <Label>Tải file lên *</Label>
            <div
              {...getRootProps()}
              className={`mt-1 border-2 border-dashed rounded-lg p-6 text-center cursor-pointer transition-colors ${
                isDragActive 
                  ? 'border-blue-400 bg-blue-50' 
                  : 'border-gray-300 hover:border-gray-400'
              } ${isLoading ? 'opacity-50 cursor-not-allowed' : ''}`}
            >
              <input {...getInputProps()} />
              
              {selectedFile ? (
                <div className="flex items-center justify-center space-x-2">
                  <File className="h-8 w-8 text-blue-500" />
                  <div className="text-left">
                    <p className="font-medium">{selectedFile.name}</p>
                    <p className="text-sm text-gray-500">
                      {(selectedFile.size / 1024 / 1024).toFixed(2)} MB
                    </p>
                  </div>
                  <Button
                    type="button"
                    variant="ghost"
                    size="sm"
                    onClick={(e) => {
                      e.stopPropagation();
                      removeFile();
                    }}
                    disabled={isLoading}
                  >
                    <X className="h-4 w-4" />
                  </Button>
                </div>
              ) : (
                <div>
                  <Upload className="mx-auto h-12 w-12 text-gray-400" />
                  <p className="mt-2 text-sm text-gray-600">
                    {isDragActive 
                      ? 'Thả file vào đây...' 
                      : 'Kéo thả file hoặc click để chọn'
                    }
                  </p>
                  <p className="text-xs text-gray-500 mt-1">
                    Hỗ trợ PDF, DOCX, DOC, TXT (tối đa 10MB)
                  </p>
                </div>
              )}
            </div>
          </div>

          <div>
            <Label htmlFor="file-questions">Số câu hỏi</Label>
            <Input
              id="file-questions"
              type="number"
              min="1"
              max="20"
              value={numberOfQuestions}
              onChange={(e) => setNumberOfQuestions(parseInt(e.target.value) || 5)}
              className="mt-1 w-32"
              disabled={isLoading}
            />
            <p className="text-sm text-gray-500 mt-1">
              Từ 1 đến 20 câu hỏi
            </p>
          </div>

          <Button 
            type="submit" 
            disabled={isLoading || !selectedFile}
            className="w-full"
          >
            {isLoading ? (
              <>
                <Loader2 className="mr-2 h-4 w-4 animate-spin" />
                Đang xử lý file...
              </>
            ) : (
              'Tạo Quiz từ File'
            )}
          </Button>
        </form>
      </CardContent>
    </Card>
  );
}
```

### 4. Quiz Preview Component

```tsx
'use client';

import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { ArrowLeft, Clock, Users, Trophy } from 'lucide-react';

interface Question {
  id: number;
  content: string;
  options: Record<string, string>;
  answer: string;
  explanation?: string;
  type: string;
  points: number;
}

interface Quiz {
  id: number;
  title: string;
  description: string;
  difficulty: string;
  duration: number;
  passing_score: number;
  status: string;
  questions: Question[];
}

interface QuizPreviewProps {
  quiz: Quiz;
  onBack: () => void;
}

export function QuizPreview({ quiz, onBack }: QuizPreviewProps) {
  return (
    <div className="space-y-6">
      {/* Header */}
      <div className="flex items-center space-x-4">
        <Button variant="ghost" onClick={onBack}>
          <ArrowLeft className="mr-2 h-4 w-4" />
          Quay lại
        </Button>
        <Badge variant="secondary">
          {quiz.status === 'draft' ? 'Chờ duyệt' : quiz.status}
        </Badge>
      </div>

      {/* Quiz Info */}
      <Card>
        <CardHeader>
          <CardTitle className="text-2xl">{quiz.title}</CardTitle>
          <p className="text-gray-600">{quiz.description}</p>
        </CardHeader>
        <CardContent>
          <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div className="flex items-center space-x-2">
              <Clock className="h-4 w-4 text-gray-500" />
              <span className="text-sm">{quiz.duration} phút</span>
            </div>
            <div className="flex items-center space-x-2">
              <Users className="h-4 w-4 text-gray-500" />
              <span className="text-sm">{quiz.questions.length} câu hỏi</span>
            </div>
            <div className="flex items-center space-x-2">
              <Trophy className="h-4 w-4 text-gray-500" />
              <span className="text-sm">Điểm đạt: {quiz.passing_score}%</span>
            </div>
          </div>
        </CardContent>
      </Card>

      {/* Questions Preview */}
      <div className="space-y-4">
        <h3 className="text-lg font-semibold">Câu hỏi ({quiz.questions.length})</h3>
        {quiz.questions.map((question, index) => (
          <Card key={question.id}>
            <CardContent className="pt-6">
              <div className="space-y-3">
                <h4 className="font-medium">
                  Câu {index + 1}: {question.content}
                </h4>
                
                <div className="grid grid-cols-1 md:grid-cols-2 gap-2">
                  {Object.entries(question.options).map(([key, value]) => (
                    <div 
                      key={key}
                      className={`p-2 rounded border ${
                        key === question.answer 
                          ? 'bg-green-50 border-green-200' 
                          : 'bg-gray-50 border-gray-200'
                      }`}
                    >
                      <span className="font-medium">{key}.</span> {value}
                      {key === question.answer && (
                        <Badge variant="secondary" className="ml-2 text-xs">
                          Đúng
                        </Badge>
                      )}
                    </div>
                  ))}
                </div>
                
                {question.explanation && (
                  <div className="bg-blue-50 border border-blue-200 rounded p-3">
                    <p className="text-sm">
                      <strong>Giải thích:</strong> {question.explanation}
                    </p>
                  </div>
                )}
              </div>
            </CardContent>
          </Card>
        ))}
      </div>

      {/* Actions */}
      <div className="flex justify-center space-x-4">
        <Button variant="outline" onClick={onBack}>
          Tạo Quiz Khác
        </Button>
        <Button onClick={() => window.location.href = `/admin/exams/${quiz.id}`}>
          Xem Chi Tiết
        </Button>
      </div>
    </div>
  );
}
```

## 🔧 Utility Functions

```typescript
// utils/auth.ts
export function getAuthToken(): string {
  // Implement based on your auth system
  return localStorage.getItem('auth_token') || '';
}

// utils/file.ts
export function formatFileSize(bytes: number): string {
  if (bytes === 0) return '0 Bytes';
  const k = 1024;
  const sizes = ['Bytes', 'KB', 'MB', 'GB'];
  const i = Math.floor(Math.log(bytes) / Math.log(k));
  return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
}

export function getFileIcon(fileType: string): string {
  if (fileType.includes('pdf')) return '📄';
  if (fileType.includes('word')) return '📝';
  if (fileType.includes('text')) return '📃';
  return '📁';
}
```

## 📦 Required Dependencies

```bash
npm install react-dropzone sonner lucide-react
```

## 🎨 Styling Notes

- Sử dụng Tailwind CSS classes như trong examples
- Components sử dụng shadcn/ui pattern
- Responsive design với grid layouts
- Loading states với spinner animations
- Toast notifications cho feedback

## 🚨 Error Handling

```typescript
// Các lỗi cần handle:
const errorMessages = {
  'FILE_TOO_LARGE': 'File quá lớn (tối đa 10MB)',
  'INVALID_FILE_TYPE': 'Loại file không được hỗ trợ',
  'EMPTY_FILE': 'File trống hoặc không đọc được',
  'AI_FAILED': 'AI không thể tạo quiz từ nội dung này',
  'NETWORK_ERROR': 'Lỗi kết nối, vui lòng thử lại',
  'UNAUTHORIZED': 'Phiên đăng nhập hết hạn',
};
```

## ✅ Testing Checklist

- [ ] Upload file PDF/DOCX/TXT thành công
- [ ] Validate file size và type
- [ ] Text prompt generation hoạt động
- [ ] Loading states hiển thị đúng
- [ ] Error handling cho các trường hợp
- [ ] Quiz preview hiển thị đầy đủ thông tin
- [ ] Responsive trên mobile/tablet
- [ ] Toast notifications hoạt động

## 🔗 Integration Notes

1. **Authentication**: Thêm Bearer token vào headers
2. **Routing**: Tích hợp với Next.js App Router
3. **State Management**: Có thể dùng Zustand/Redux nếu cần
4. **File Storage**: Files được xử lý tạm thời, không lưu trữ lâu dài