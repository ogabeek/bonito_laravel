<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * * BASE REQUEST: Shared teacher validation (abstract)
 * ? Extended by CreateTeacherRequest & UpdateTeacherRequest
 */
abstract class TeacherRequest extends FormRequest
{
    public function authorize(): bool
    {
        return session()->has('admin_authenticated');
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Teacher name is required.',
            'password.required' => 'PIN is required.',
            'password.min' => 'PIN must be at least :min characters.',
        ];
    }

    protected function passwordMinLength(): int
    {
        return config('validation.password_min_length', 4);
    }
}
