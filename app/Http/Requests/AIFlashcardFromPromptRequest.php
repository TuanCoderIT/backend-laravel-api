<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AIFlashcardFromPromptRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'prompt' => ['required', 'string', 'min:5', 'max:2000'],
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
            'prompt.required' => 'Vui lòng nhập prompt để tạo flashcard.',
            'prompt.min' => 'Prompt phải có ít nhất 5 ký tự.',
            'prompt.max' => 'Prompt không được vượt quá 2000 ký tự.',
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
