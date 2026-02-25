<?php

namespace App\Http\Requests;

/**
 * * REQUEST: Teacher Login validation
 */
class TeacherLoginRequest extends BaseLoginRequest
{
    public function messages(): array
    {
        return [
            'password.required' => 'PIN is required',
            'password.min' => 'PIN must be at least 4 characters',
        ];
    }
}
