<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AIQuizFromFileRequest extends FormRequest
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
            'number_of_questions' => [
                'nullable',
                'integer',
                'min:1',
                'max:20',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'file.required' => 'Please upload a file.',
            'file.file' => 'The uploaded item must be a valid file.',
            'file.mimes' => 'Only PDF, DOCX, DOC, and TXT files are supported.',
            'file.max' => 'File size cannot exceed 10MB.',
            'number_of_questions.integer' => 'Number of questions must be a valid number.',
            'number_of_questions.min' => 'Minimum 1 question is required.',
            'number_of_questions.max' => 'Maximum 20 questions allowed.',
        ];
    }
}