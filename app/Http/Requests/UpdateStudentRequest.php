<?php

namespace App\Http\Requests;

class UpdateStudentRequest extends StudentRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return session()->has('admin_authenticated');
    }
}
