<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Log;

class CourseUpdateRequest extends FormRequest
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
    public function rules()
    {
        Log::info('Incoming request', [
            'all' => $this->all(),
            'isMultipart' => $this->isJson() ? false : true,
            'contentType' => $this->headers->get('Content-Type'),
        ]);

        return [
            'title' => 'sometimes|required|string|max:255',
            'slug' => 'sometimes|nullable|string|max:255|unique:courses,slug,' . $this->route('course'),
            'description' => 'nullable|string',
            'thumbnail' => 'nullable|file|image|max:2048',
            'is_public' => 'boolean',
            'category_id' => 'nullable|exists:categories,id',
            'price_token' => 'nullable|integer|min:0',
        ];
    }
}
