<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StoreQuestionRequest extends FormRequest
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
            'content' => 'required|string|max:1000',
            'options' => 'required_if:type,multiple_choice,true_false|array',
            'options.*' => 'string|max:255',
            'answer' => 'required|string|max:255',
            'explanation' => 'nullable|string|max:500',
            'type' => 'required|in:multiple_choice,true_false,short_answer,essay',
            'points' => 'required|integer|min:1|max:100',
        ];
    }
}
