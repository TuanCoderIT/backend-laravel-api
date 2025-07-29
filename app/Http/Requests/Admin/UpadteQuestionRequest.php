<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class UpadteQuestionRequest extends FormRequest
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
            'content' => 'sometimes|required|string',
            'options' => 'sometimes|required_if:type,multiple_choice,true_false|array',
            'answer' => 'sometimes|required|string|max:255',
            'type' => 'sometimes|required|in:multiple_choice,true_false,short_answer,essay',
            'points' => 'sometimes|required|integer|min:1',
            'explanation' => 'nullable|string|max:500',
        ];
    }
}
