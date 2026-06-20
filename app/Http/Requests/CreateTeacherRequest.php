<?php

namespace App\Http\Requests;

/**
 * * REQUEST: Create Teacher validation
 * ? Password is required when creating a teacher.
 */
class CreateTeacherRequest extends TeacherRequest
{
    public function rules(): array
    {
        return [
            ...parent::rules(),
            'password' => ['required', 'string', 'min:'.$this->passwordMinLength()],
        ];
    }
}
