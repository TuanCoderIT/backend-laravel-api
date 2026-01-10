<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class LessonStoreRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            // 'chapter_id' => 'required|exists:course_chapters,id',
            'title' => 'required|string|max:255',
            'content' => 'nullable|string',
            'type' => 'required|in:video,text',
            'video_url' => 'nullable|url',
            'duration_seconds' => 'nullable|integer|min:0',
            'is_free_preview' => 'boolean',
            'order' => 'integer|min:0',
        ];
    }
}
