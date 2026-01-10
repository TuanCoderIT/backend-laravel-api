<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class DocumentStoreRequest extends FormRequest
{
    /**
     * Xác định xem user có quyền thực hiện request này không
     * Vì đã có middleware admin, nên return true
     */
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation()
    {
        // Chuyển đổi chuỗi "false" thành boolean false, và "true" thành boolean true.
        // Nếu nó không phải là chuỗi này, Laravel validation sẽ xử lý tiếp.
        $this->merge([
            'is_premium' => filter_var($this->is_premium, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE)
        ]);
    }

    /**
     * Get the validation rules that apply to the request.
     * Validation rules cho việc tạo document mới
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'category_id' => 'nullable|exists:categories,id',
            'is_premium' => 'boolean',
            'status' => 'nullable|in:draft,published,archived',
            'price_token' => 'nullable|integer|min:0',
            'file' => 'required|file|mimes:pdf|max:51200',
            'thumbnail' => 'nullable|file|image|max:2048',
        ];
    }

    /**
     * Custom messages cho validation
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'file.required' => 'File PDF là bắt buộc.',
            'file.mimes' => 'Chỉ chấp nhận file PDF.',
            'file.max' => 'Kích thước file tối đa là 50MB.',
            'title.required' => 'Tiêu đề là bắt buộc.',
            'category_id.exists' => 'Danh mục không tồn tại.',
        ];
    }
}
