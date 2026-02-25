<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * * REQUEST: Create Teacher validation
 */
class CreateTeacherRequest extends FormRequest
{
    public function authorize(): bool
    {
        return session()->has('admin_authenticated');
    }

    public function rules(): array
    {
        $minLength = config('validation.password_min_length', 4);

        return [
            'name' => ['required', 'string', 'max:255'],
            'password' => ['required', 'string', "min:{$minLength}"],
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
}
