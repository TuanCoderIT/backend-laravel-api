<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class DocumentUpdateRequest extends FormRequest
{
    /**
     * Xác định xem user có quyền thực hiện request này không
     * Vì đã có middleware admin, nên return true
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     * Validation rules cho việc cập nhật document
     * File PDF là optional khi update (chỉ update khi muốn thay đổi file)
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'title' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
            'category_id' => 'nullable|exists:categories,id',
            'is_premium' => 'nullable|boolean',
            'status' => 'nullable|in:draft,published,archived',
            'price_tokens' => 'nullable|integer|min:0',
            // File PDF là optional khi update, tối đa 50MB
            'file' => 'sometimes|file|mimes:pdf|max:51200',
            'thumbnail' => ['nullable', 'image', 'max:2048'], // JPG, PNG, WEBP
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
            'file.mimes' => 'Chỉ chấp nhận file PDF.',
            'file.max' => 'Kích thước file tối đa là 50MB.',
            'title.required' => 'Tiêu đề là bắt buộc.',
            'category_id.exists' => 'Danh mục không tồn tại.',
        ];
    }
}
