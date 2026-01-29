<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * ! BASE REQUEST: Login Validation (Abstract)
 * * Purpose: Shared password validation for admin and teacher login
 * * Why: DRY principle - avoid duplicating login validation logic
 * * What: Validates password meets minimum requirements
 * ? Extended by AdminLoginRequest and TeacherLoginRequest
 */
abstract class BaseLoginRequest extends FormRequest
{
    /**
     * * Authorization: Anyone can attempt to login
     */
    public function authorize(): bool
    {
        // * No restrictions on login attempts
        // ? Actual authentication happens in controller after validation
        return true;
    }

    /**
     * * Validation Rules: Password requirements
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            // * Password must be: present, string type, at least 4 characters
            'password' => ['required', 'string', 'min:4'],
        ];
    }

    /**
     * * Custom Error Messages: User-friendly validation feedback
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            // ? Format: 'field.rule' => 'Custom message'
            'password.required' => 'Password is required',
            'password.min' => 'Password must be at least 4 characters',
        ];
    }
}
