<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * * BASE REQUEST: Shared login validation (abstract)
 * ? Extended by AdminLoginRequest and TeacherLoginRequest
 */
abstract class BaseLoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Anyone can attempt login
    }

    public function rules(): array
    {
        return [
            'password' => ['required', 'string', 'min:4'],
        ];
    }

    public function messages(): array
    {
        return [
            'password.required' => 'Password is required',
            'password.min' => 'Password must be at least 4 characters',
        ];
    }
}
