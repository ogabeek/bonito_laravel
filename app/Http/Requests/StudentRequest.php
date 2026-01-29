<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * ! BASE REQUEST: Student Validation (Abstract)
 * * Purpose: Shared validation rules for creating/updating students
 * * Why: DRY principle - define common rules once, reuse in CreateStudentRequest & UpdateStudentRequest
 * * What: Base class that child classes extend
 * ? Abstract = cannot be used directly, must be extended
 */
abstract class StudentRequest extends FormRequest
{
    /**
     * * Authorization: Only logged-in admins can create/update students
     */
    public function authorize(): bool
    {
        // ! Security: Check if admin is authenticated via session
        // ? This prevents unauthorized users from managing students
        return session()->has('admin_authenticated');
    }

    /**
     * * Validation Rules: Common rules for all student operations
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            // * Required fields
            'name' => 'required|string|max:255',  // Student name (mandatory)

            // * Optional fields (nullable = can be empty)
            'parent_name' => 'nullable|string|max:255',  // Parent/guardian name
            'email' => 'nullable|email|max:255',         // Must be valid email format if provided
            'goal' => 'nullable|string',                 // Student's learning goals
            'description' => 'nullable|string',          // Additional notes about student
        ];
    }
}
