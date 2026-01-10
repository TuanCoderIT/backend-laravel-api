<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UserAIQuizFromFileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            // File upload (giữ nguyên)
            'file' => [
                'required',
                'file',
                'mimes:pdf,docx,doc,txt',
                'max:10240', // 10MB
            ],
            'number_of_questions' => [
                'nullable',
                'integer',
                'min:1',
                'max:20',
            ],
            
            // Thông tin Quiz đầy đủ như Admin
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'category_id' => 'required|exists:categories,id',
            'difficulty' => 'required|in:Beginner,Intermediate,Advanced',
            'duration' => 'required|integer|min:1',
            'color' => 'nullable|string|max:7',
            'passing_score' => 'required|integer|min:0|max:100',
            'max_attempts' => 'required|integer|min:1',
            'learning_objectives' => 'nullable|array',
            'prerequisites' => 'nullable|array', 
            'tags' => 'nullable|array',
            'price_token' => 'nullable|integer|min:0|max:1000000',
        ];
    }

    public function messages(): array
    {
        return [
            // File validation messages
            'file.required' => 'Vui lòng tải lên file.',
            'file.file' => 'File tải lên không hợp lệ.',
            'file.mimes' => 'Chỉ hỗ trợ file PDF, DOCX, DOC và TXT.',
            'file.max' => 'Kích thước file không được vượt quá 10MB.',
            'number_of_questions.integer' => 'Số câu hỏi phải là số nguyên.',
            'number_of_questions.min' => 'Tối thiểu 1 câu hỏi.',
            'number_of_questions.max' => 'Tối đa 20 câu hỏi.',
            
            // Quiz info validation messages
            'title.required' => 'Tiêu đề Quiz là bắt buộc.',
            'title.max' => 'Tiêu đề không được vượt quá 255 ký tự.',
            'category_id.required' => 'Vui lòng chọn danh mục.',
            'category_id.exists' => 'Danh mục không tồn tại.',
            'difficulty.required' => 'Vui lòng chọn độ khó.',
            'difficulty.in' => 'Độ khó phải là Beginner, Intermediate hoặc Advanced.',
            'duration.required' => 'Thời gian làm bài là bắt buộc.',
            'duration.min' => 'Thời gian làm bài tối thiểu 1 phút.',
            'passing_score.required' => 'Điểm đạt là bắt buộc.',
            'passing_score.min' => 'Điểm đạt tối thiểu 0%.',
            'passing_score.max' => 'Điểm đạt tối đa 100%.',
            'max_attempts.required' => 'Số lần làm bài là bắt buộc.',
            'max_attempts.min' => 'Tối thiểu 1 lần làm bài.',
            'price_token.integer' => 'Giá token phải là số nguyên.',
            'price_token.min' => 'Giá token tối thiểu 0.',
            'price_token.max' => 'Giá token tối đa 1,000,000.',
        ];
    }
}