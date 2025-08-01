<?php

namespace App\Http\Requests\Public;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProfileRequest extends FormRequest
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
            'name' => 'required|string|max:255',
            'password' => 'nullable|string|min:8',
            'gender' => 'required|string|in:male,female,other',
            'avatar' => 'nullable|image|max:2048',
            'phone_number' => 'nullable|string|max:20',
            'date_of_birth' => 'nullable|date',
            'bio' => 'nullable|string|max:1000',
        ];
    }
}
