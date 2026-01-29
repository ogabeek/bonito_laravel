<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * * BASE REQUEST: Shared student validation (abstract)
 * ? Extended by CreateStudentRequest & UpdateStudentRequest
 */
abstract class StudentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return session()->has('admin_authenticated');
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'parent_name' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255',
            'goal' => 'nullable|string',
            'description' => 'nullable|string',
        ];
    }
}
