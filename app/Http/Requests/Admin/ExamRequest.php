<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class ExamRequest extends FormRequest
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
            'status' => 'required|in:Draft,Published,Archived',
            'price_token' => 'nullable|integer|min:0|max:1000000',
        ];
    }
}
