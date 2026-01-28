<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

abstract class StudentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return session()->has('admin_authenticated');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
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
