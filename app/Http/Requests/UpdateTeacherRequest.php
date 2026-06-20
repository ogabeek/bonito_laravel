<?php

namespace App\Http\Requests;

/**
 * * REQUEST: Update Teacher validation
 * ? Password is optional on update (left blank = keep current); adds contact/zoom fields.
 */
class UpdateTeacherRequest extends TeacherRequest
{
    public function rules(): array
    {
        return [
            ...parent::rules(),
            'password' => ['nullable', 'string', 'min:'.$this->passwordMinLength()],
            'contact' => ['nullable', 'string', 'max:255'],
            'zoom_link' => ['nullable', 'url', 'max:500'],
            'zoom_id' => ['nullable', 'string', 'max:50'],
            'zoom_passcode' => ['nullable', 'string', 'max:50'],
        ];
    }
}
