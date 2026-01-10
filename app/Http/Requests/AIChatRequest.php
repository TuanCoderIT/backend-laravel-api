<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AIChatRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'message' => 'required|string|max:1000',
            'context_type' => 'nullable|string|in:course,exam,general',
            'context_id' => 'nullable|integer|exists:courses,id|exists:exams,id',
        ];
    }

    public function messages(): array
    {
        return [
            'message.required' => 'Vui lòng nhập tin nhắn',
            'message.max' => 'Tin nhắn không được quá 1000 ký tự',
            'context_type.in' => 'Loại context không hợp lệ',
            'context_id.exists' => 'ID context không tồn tại',
        ];
    }
}