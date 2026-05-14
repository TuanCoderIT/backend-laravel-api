<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class NotificationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'user_id' => 'required|exists:users,id',
            'type' => 'required|string|max:255',
            'title' => 'required|string|max:255',
            'message' => 'required|string|max:1000',
            'data' => 'nullable|array',
            'action_url' => 'nullable|string|url',
            'icon' => 'nullable|string|max:50'
        ];
    }

    public function messages(): array
    {
        return [
            'user_id.required' => 'User ID là bắt buộc',
            'user_id.exists' => 'User không tồn tại',
            'type.required' => 'Loại thông báo là bắt buộc',
            'title.required' => 'Tiêu đề là bắt buộc',
            'title.max' => 'Tiêu đề không được quá 255 ký tự',
            'message.required' => 'Nội dung thông báo là bắt buộc',
            'message.max' => 'Nội dung không được quá 1000 ký tự',
            'action_url.url' => 'URL không hợp lệ'
        ];
    }
}