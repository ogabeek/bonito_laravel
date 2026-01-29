<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validates data when creating a new teacher.
 *
 * Authorization: Only authenticated admins can create teachers.
 */
class CreateTeacherRequest extends FormRequest
{
    /**
     * Only logged-in admins can create teachers.
     */
    public function authorize(): bool
    {
        return session()->has('admin_authenticated');
    }

    /**
     * Validation rules for teacher creation.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $minLength = config('validation.password_min_length', 4);

        return [
            'name' => ['required', 'string', 'max:255'],
            'password' => ['required', 'string', "min:{$minLength}"],
        ];
    }

    /**
     * Custom error messages for validation.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Teacher name is required.',
            'password.required' => 'Password is required.',
            'password.min' => 'Password must be at least :min characters.',
        ];
    }
}
