<?php

namespace App\Http\Requests;

use App\Support\Countries;
use App\Support\Languages;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

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
            'country' => ['nullable', Rule::in(Countries::codes())],
            'language' => ['nullable', Rule::in(Languages::codes())],
            'goal' => 'nullable|string',
            'description' => 'nullable|string',
        ];
    }
}
