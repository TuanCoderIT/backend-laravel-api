# User AI Quiz Frontend Implementation Guide

## Tổng Quan
Hướng dẫn tích hợp giao diện tạo Quiz bằng AI với thông tin đầy đủ cho người dùng.

## 1. Component Structure

### Main Component: `CreateAIQuizForm`
```typescript
interface CreateAIQuizFormProps {
  onSuccess?: (quiz: Quiz) => void;
  onError?: (error: string) => void;
}

interface QuizFormData {
  // File upload
  file: File | null;
  number_of_questions: number;
  
  // Quiz information
  title: string;
  description: string;
  category_id: number;
  difficulty: 'Beginner' | 'Intermediate' | 'Advanced';
  duration: number;
  passing_score: number;
  max_attempts: number;
  
  // Optional fields
  color?: string;
  price_token?: number;
  learning_objectives?: string[];
  prerequisites?: string[];
  tags?: string[];
}
```

## 2. Form Implementation

### Step 1: File Upload Section
```tsx
const FileUploadSection = ({ formData, setFormData, errors }) => {
  const handleFileChange = (e: React.ChangeEvent<HTMLInputElement>) => {
    const file = e.target.files?.[0];
    if (file) {
      // Validate file type and size
      const allowedTypes = ['application/pdf', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'application/msword', 'text/plain'];
      const maxSize = 10 * 1024 * 1024; // 10MB
      
      if (!allowedTypes.includes(file.type)) {
        setErrors(prev => ({ ...prev, file: 'Chỉ hỗ trợ file PDF, DOCX, DOC và TXT' }));
        return;
      }
      
      if (file.size > maxSize) {
        setErrors(prev => ({ ...prev, file: 'File không được vượt quá 10MB' }));
        return;
      }
      
      setFormData(prev => ({ ...prev, file }));
      setErrors(prev => ({ ...prev, file: null }));
    }
  };

  return (
    <div className="space-y-4">
      <h3 className="text-lg font-semibold">📁 Upload File</h3>
      
      <div className="border-2 border-dashed border-gray-300 rounded-lg p-6">
        <input
          type="file"
          accept=".pdf,.docx,.doc,.txt"
          onChange={handleFileChange}
          className="hidden"
          id="file-upload"
        />
        <label htmlFor="file-upload" className="cursor-pointer">
          <div className="text-center">
            <div className="text-4xl mb-2">📄</div>
            <p className="text-gray-600">
              {formData.file ? formData.file.name : 'Chọn file PDF, DOCX, DOC hoặc TXT'}
            </p>
            <p className="text-sm text-gray-400 mt-1">Tối đa 10MB</p>
          </div>
        </label>
      </div>
      
      {errors.file && (
        <p className="text-red-500 text-sm">{errors.file}</p>
      )}
      
      <div>
        <label className="block text-sm font-medium mb-1">
          Số câu hỏi
        </label>
        <input
          type="number"
          min="1"
          max="20"
          value={formData.number_of_questions}
          onChange={(e) => setFormData(prev => ({ 
            ...prev, 
            number_of_questions: parseInt(e.target.value) || 5 
          }))}
          className="w-full px-3 py-2 border rounded-md"
        />
      </div>
    </div>
  );
};
```

### Step 2: Basic Quiz Information
```tsx
const BasicInfoSection = ({ formData, setFormData, categories, errors }) => {
  return (
    <div className="space-y-4">
      <h3 className="text-lg font-semibold">📝 Thông Tin Quiz</h3>
      
      <div>
        <label className="block text-sm font-medium mb-1">
          Tiêu đề Quiz *
        </label>
        <input
          type="text"
          value={formData.title}
          onChange={(e) => setFormData(prev => ({ ...prev, title: e.target.value }))}
          className="w-full px-3 py-2 border rounded-md"
          placeholder="Nhập tiêu đề quiz..."
          maxLength={255}
          required
        />
        {errors.title && <p className="text-red-500 text-sm mt-1">{errors.title}</p>}
      </div>
      
      <div>
        <label className="block text-sm font-medium mb-1">
          Mô tả
        </label>
        <textarea
          value={formData.description}
          onChange={(e) => setFormData(prev => ({ ...prev, description: e.target.value }))}
          className="w-full px-3 py-2 border rounded-md"
          rows={3}
          placeholder="Mô tả ngắn về quiz này..."
        />
      </div>
      
      <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div>
          <label className="block text-sm font-medium mb-1">
            Danh mục *
          </label>
          <select
            value={formData.category_id}
            onChange={(e) => setFormData(prev => ({ ...prev, category_id: parseInt(e.target.value) }))}
            className="w-full px-3 py-2 border rounded-md"
            required
          >
            <option value="">Chọn danh mục</option>
            {categories.map(category => (
              <option key={category.id} value={category.id}>
                {category.name}
              </option>
            ))}
          </select>
          {errors.category_id && <p className="text-red-500 text-sm mt-1">{errors.category_id}</p>}
        </div>
        
        <div>
          <label className="block text-sm font-medium mb-1">
            Độ khó *
          </label>
          <select
            value={formData.difficulty}
            onChange={(e) => setFormData(prev => ({ ...prev, difficulty: e.target.value as any }))}
            className="w-full px-3 py-2 border rounded-md"
            required
          >
            <option value="Beginner">Beginner</option>
            <option value="Intermediate">Intermediate</option>
            <option value="Advanced">Advanced</option>
          </select>
        </div>
      </div>
    </div>
  );
};
```

### Step 3: Quiz Settings
```tsx
const QuizSettingsSection = ({ formData, setFormData, errors }) => {
  return (
    <div className="space-y-4">
      <h3 className="text-lg font-semibold">⚙️ Cài Đặt Quiz</h3>
      
      <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div>
          <label className="block text-sm font-medium mb-1">
            Thời gian (phút) *
          </label>
          <input
            type="number"
            min="1"
            value={formData.duration}
            onChange={(e) => setFormData(prev => ({ ...prev, duration: parseInt(e.target.value) || 1 }))}
            className="w-full px-3 py-2 border rounded-md"
            required
          />
        </div>
        
        <div>
          <label className="block text-sm font-medium mb-1">
            Điểm đạt (%) *
          </label>
          <input
            type="number"
            min="0"
            max="100"
            value={formData.passing_score}
            onChange={(e) => setFormData(prev => ({ ...prev, passing_score: parseInt(e.target.value) || 70 }))}
            className="w-full px-3 py-2 border rounded-md"
            required
          />
        </div>
        
        <div>
          <label className="block text-sm font-medium mb-1">
            Số lần làm tối đa *
          </label>
          <input
            type="number"
            min="1"
            value={formData.max_attempts}
            onChange={(e) => setFormData(prev => ({ ...prev, max_attempts: parseInt(e.target.value) || 3 }))}
            className="w-full px-3 py-2 border rounded-md"
            required
          />
        </div>
      </div>
      
      <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div>
          <label className="block text-sm font-medium mb-1">
            Màu sắc
          </label>
          <input
            type="color"
            value={formData.color || '#3B82F6'}
            onChange={(e) => setFormData(prev => ({ ...prev, color: e.target.value }))}
            className="w-full h-10 border rounded-md"
          />
        </div>
        
        <div>
          <label className="block text-sm font-medium mb-1">
            Giá token
          </label>
          <input
            type="number"
            min="0"
            max="1000000"
            value={formData.price_token || 0}
            onChange={(e) => setFormData(prev => ({ ...prev, price_token: parseInt(e.target.value) || 0 }))}
            className="w-full px-3 py-2 border rounded-md"
            placeholder="0 = Miễn phí"
          />
        </div>
      </div>
    </div>
  );
};
```

### Step 4: Advanced Options (Optional - AI sẽ tự tạo nếu không nhập)
```tsx
const AdvancedOptionsSection = ({ formData, setFormData }) => {
  const [isExpanded, setIsExpanded] = useState(false);
  
  const handleArrayChange = (field: string, value: string) => {
    const items = value.split('\n').filter(item => item.trim());
    setFormData(prev => ({ ...prev, [field]: items }));
  };
  
  return (
    <div className="space-y-4">
      <button
        type="button"
        onClick={() => setIsExpanded(!isExpanded)}
        className="flex items-center space-x-2 text-lg font-semibold hover:text-blue-600"
      >
        <span>{isExpanded ? '🔽' : '▶️'}</span>
        <span>Tùy Chọn Nâng Cao</span>
        <span className="text-sm text-gray-500 font-normal">(AI sẽ tự tạo nếu bỏ trống)</span>
      </button>
      
      {isExpanded && (
        <div className="space-y-4 pl-6 border-l-2 border-gray-200">
          <div className="bg-blue-50 p-3 rounded-md mb-4">
            <p className="text-sm text-blue-700">
              💡 <strong>Mẹo:</strong> Bạn có thể để trống các trường bên dưới. AI sẽ tự động tạo ra nội dung phù hợp dựa trên file bạn upload.
            </p>
          </div>
          
          <div>
            <label className="block text-sm font-medium mb-1">
              Mục tiêu học tập
              <span className="text-gray-400 text-xs ml-1">(AI tự tạo nếu trống)</span>
            </label>
            <textarea
              value={formData.learning_objectives?.join('\n') || ''}
              onChange={(e) => handleArrayChange('learning_objectives', e.target.value)}
              className="w-full px-3 py-2 border rounded-md"
              rows={3}
              placeholder="Để trống để AI tự tạo, hoặc nhập mỗi mục tiêu một dòng..."
            />
          </div>
          
          <div>
            <label className="block text-sm font-medium mb-1">
              Kiến thức tiên quyết
              <span className="text-gray-400 text-xs ml-1">(AI tự tạo nếu trống)</span>
            </label>
            <textarea
              value={formData.prerequisites?.join('\n') || ''}
              onChange={(e) => handleArrayChange('prerequisites', e.target.value)}
              className="w-full px-3 py-2 border rounded-md"
              rows={3}
              placeholder="Để trống để AI tự tạo, hoặc nhập mỗi kiến thức một dòng..."
            />
          </div>
          
          <div>
            <label className="block text-sm font-medium mb-1">
              Tags
              <span className="text-gray-400 text-xs ml-1">(AI tự tạo nếu trống)</span>
            </label>
            <textarea
              value={formData.tags?.join('\n') || ''}
              onChange={(e) => handleArrayChange('tags', e.target.value)}
              className="w-full px-3 py-2 border rounded-md"
              rows={2}
              placeholder="Để trống để AI tự tạo, hoặc nhập mỗi tag một dòng..."
            />
          </div>
        </div>
      )}
    </div>
  );
};
```

## 3. Main Form Component

```tsx
const CreateAIQuizForm: React.FC<CreateAIQuizFormProps> = ({ onSuccess, onError }) => {
  const [formData, setFormData] = useState<QuizFormData>({
    file: null,
    number_of_questions: 5,
    title: '',
    description: '',
    category_id: 0,
    difficulty: 'Beginner',
    duration: 30,
    passing_score: 70,
    max_attempts: 3,
    color: '#3B82F6',
    price_token: 0,
    learning_objectives: [],
    prerequisites: [],
    tags: [],
  });
  
  const [categories, setCategories] = useState([]);
  const [errors, setErrors] = useState({});
  const [isLoading, setIsLoading] = useState(false);
  
  // Load categories on mount
  useEffect(() => {
    fetch('/api/categories')
      .then(res => res.json())
      .then(setCategories)
      .catch(console.error);
  }, []);
  
  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    
    if (!formData.file) {
      setErrors({ file: 'Vui lòng chọn file' });
      return;
    }
    
    setIsLoading(true);
    setErrors({});
    
    try {
      const formDataToSend = new FormData();
      
      // File upload
      formDataToSend.append('file', formData.file);
      formDataToSend.append('number_of_questions', formData.number_of_questions.toString());
      
      // Quiz info
      formDataToSend.append('title', formData.title);
      formDataToSend.append('description', formData.description);
      formDataToSend.append('category_id', formData.category_id.toString());
      formDataToSend.append('difficulty', formData.difficulty);
      formDataToSend.append('duration', formData.duration.toString());
      formDataToSend.append('passing_score', formData.passing_score.toString());
      formDataToSend.append('max_attempts', formData.max_attempts.toString());
      
      // Optional fields
      if (formData.color) formDataToSend.append('color', formData.color);
      if (formData.price_token) formDataToSend.append('price_token', formData.price_token.toString());
      
      // Arrays - only send if user provided them
      if (formData.learning_objectives?.length && formData.learning_objectives.some(obj => obj.trim())) {
        formDataToSend.append('learning_objectives', JSON.stringify(formData.learning_objectives.filter(obj => obj.trim())));
      }
      if (formData.prerequisites?.length && formData.prerequisites.some(pre => pre.trim())) {
        formDataToSend.append('prerequisites', JSON.stringify(formData.prerequisites.filter(pre => pre.trim())));
      }
      if (formData.tags?.length && formData.tags.some(tag => tag.trim())) {
        formDataToSend.append('tags', JSON.stringify(formData.tags.filter(tag => tag.trim())));
      }
      
      const response = await fetch('/api/user/exams/ai-generate-from-file', {
        method: 'POST',
        body: formDataToSend,
        headers: {
          'Authorization': `Bearer ${localStorage.getItem('token')}`
        }
      });
      
      const result = await response.json();
      
      if (!response.ok) {
        if (result.errors) {
          setErrors(result.errors);
        } else {
          throw new Error(result.message || 'Có lỗi xảy ra');
        }
        return;
      }
      
      onSuccess?.(result.data);
      
    } catch (error) {
      console.error('Error creating quiz:', error);
      onError?.(error.message);
    } finally {
      setIsLoading(false);
    }
  };
  
  return (
    <form onSubmit={handleSubmit} className="max-w-4xl mx-auto space-y-8">
      <div className="bg-white rounded-lg shadow-md p-6">
        <h2 className="text-2xl font-bold mb-6">🤖 Tạo Quiz Bằng AI</h2>
        
        <FileUploadSection 
          formData={formData} 
          setFormData={setFormData} 
          errors={errors}
          setErrors={setErrors}
        />
      </div>
      
      <div className="bg-white rounded-lg shadow-md p-6">
        <BasicInfoSection 
          formData={formData} 
          setFormData={setFormData} 
          categories={categories}
          errors={errors}
        />
      </div>
      
      <div className="bg-white rounded-lg shadow-md p-6">
        <QuizSettingsSection 
          formData={formData} 
          setFormData={setFormData} 
          errors={errors}
        />
      </div>
      
      <div className="bg-white rounded-lg shadow-md p-6">
        <AdvancedOptionsSection 
          formData={formData} 
          setFormData={setFormData} 
        />
      </div>
      
      <div className="flex justify-end space-x-4">
        <button
          type="button"
          className="px-6 py-2 border border-gray-300 rounded-md hover:bg-gray-50"
          onClick={() => window.history.back()}
        >
          Hủy
        </button>
        <button
          type="submit"
          disabled={isLoading || !formData.file}
          className="px-6 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed flex items-center space-x-2"
        >
          {isLoading && <div className="animate-spin w-4 h-4 border-2 border-white border-t-transparent rounded-full" />}
          <span>{isLoading ? 'Đang tạo Quiz...' : '🚀 Tạo Quiz'}</span>
        </button>
      </div>
    </form>
  );
};
```

## 4. Integration Points

### Navigation Menu
```tsx
// Thêm vào menu tạo quiz
<Link href="/quizzes/create-ai" className="flex items-center space-x-2">
  <span>🤖</span>
  <span>Tạo Quiz Bằng AI</span>
</Link>
```

### Page Route
```tsx
// pages/quizzes/create-ai.tsx hoặc app/quizzes/create-ai/page.tsx
export default function CreateAIQuizPage() {
  const router = useRouter();
  
  const handleSuccess = (quiz) => {
    toast.success('Quiz được tạo thành công!');
    router.push(`/quizzes/${quiz.id}`);
  };
  
  const handleError = (error) => {
    toast.error(error);
  };
  
  return (
    <div className="container mx-auto py-8">
      <CreateAIQuizForm 
        onSuccess={handleSuccess}
        onError={handleError}
      />
    </div>
  );
}
```

## 5. UX Improvements

### Progress Indicator
```tsx
const steps = [
  { id: 1, name: 'Upload File', icon: '📁' },
  { id: 2, name: 'Thông Tin Quiz', icon: '📝' },
  { id: 3, name: 'Cài Đặt', icon: '⚙️' },
  { id: 4, name: 'Hoàn Thành', icon: '✅' }
];

const ProgressSteps = ({ currentStep }) => (
  <div className="flex items-center justify-center mb-8">
    {steps.map((step, index) => (
      <div key={step.id} className="flex items-center">
        <div className={`flex items-center justify-center w-10 h-10 rounded-full ${
          currentStep >= step.id ? 'bg-blue-600 text-white' : 'bg-gray-200'
        }`}>
          <span>{step.icon}</span>
        </div>
        <span className="ml-2 text-sm font-medium">{step.name}</span>
        {index < steps.length - 1 && (
          <div className="w-16 h-1 bg-gray-200 mx-4">
            <div 
              className={`h-full bg-blue-600 transition-all ${
                currentStep > step.id ? 'w-full' : 'w-0'
              }`}
            />
          </div>
        )}
      </div>
    ))}
  </div>
);
```

### Loading States
```tsx
const LoadingOverlay = ({ message }) => (
  <div className="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
    <div className="bg-white rounded-lg p-8 max-w-md w-full mx-4">
      <div className="text-center">
        <div className="animate-spin w-12 h-12 border-4 border-blue-600 border-t-transparent rounded-full mx-auto mb-4" />
        <h3 className="text-lg font-semibold mb-2">🤖 AI đang tạo Quiz</h3>
        <p className="text-gray-600">{message}</p>
        <div className="mt-4 text-sm text-gray-500">
          Quá trình này có thể mất 30-60 giây...
        </div>
      </div>
    </div>
  </div>
);
```

## 6. Error Handling

### Validation Display
```tsx
const ErrorMessage = ({ error }) => (
  error ? (
    <div className="mt-1 text-sm text-red-600 flex items-center">
      <span className="mr-1">⚠️</span>
      {error}
    </div>
  ) : null
);
```

### Network Error Handling
```tsx
const handleNetworkError = (error) => {
  if (error.name === 'NetworkError') {
    return 'Lỗi kết nối mạng. Vui lòng thử lại.';
  }
  if (error.status === 413) {
    return 'File quá lớn. Vui lòng chọn file nhỏ hơn 10MB.';
  }
  if (error.status === 422) {
    return 'Dữ liệu không hợp lệ. Vui lòng kiểm tra lại.';
  }
  return error.message || 'Có lỗi xảy ra. Vui lòng thử lại.';
};
```

---

**Kết quả:** Người dùng có thể tạo Quiz bằng AI với đầy đủ thông tin như Admin, mang lại trải nghiệm tốt hơn và kiểm soát hoàn toàn nội dung Quiz của mình.