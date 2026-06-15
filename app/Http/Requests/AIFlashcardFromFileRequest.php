<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AIFlashcardFromFileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'file' => [
                'required',
                'file',
                'mimes:pdf,docx,doc,txt',
                'max:10240', // 10MB
            ],
            'number_of_cards' => ['nullable', 'integer', 'min:1', 'max:30'],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'category_id' => ['nullable', 'exists:categories,id'],
            'visibility' => ['nullable', 'in:private,public'],
        ];
    }

    public function messages(): array
    {
        return [
            'file.required' => 'Vui lòng tải lên file.',
            'file.file' => 'File tải lên không hợp lệ.',
            'file.mimes' => 'Chỉ hỗ trợ file PDF, DOCX, DOC và TXT.',
            'file.max' => 'Kích thước file không được vượt quá 10MB.',
            'number_of_cards.integer' => 'Số lượng thẻ phải là số nguyên.',
            'number_of_cards.min' => 'Số lượng thẻ tối thiểu là 1.',
            'number_of_cards.max' => 'Số lượng thẻ tối đa là 30.',
            'title.required' => 'Tiêu đề bộ thẻ là bắt buộc.',
            'title.max' => 'Tiêu đề không được vượt quá 255 ký tự.',
            'category_id.exists' => 'Danh mục đã chọn không hợp lệ.',
            'visibility.in' => 'Chế độ hiển thị phải là private hoặc public.',
        ];
    }
}
