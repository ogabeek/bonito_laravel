<?php

namespace App\Http\Requests;

/**
 * ! FORM REQUEST: Update Student
 * * Purpose: Validates data when updating an existing student
 * * Why: Separate class for semantic clarity and future extensibility
 * * What: Currently uses all parent StudentRequest rules
 *
 * ? Why keep this instead of using StudentRequest directly?
 * - Type hinting in controller shows intent: "This updates students"
 * - Easy to add update-specific rules later (e.g., unique email except current)
 * - Follows Laravel convention: separate requests for create/update operations
 *
 * TODO: Consider adding "unique email except this student" validation if needed
 */
class UpdateStudentRequest extends StudentRequest
{
    // * Authorization and validation rules inherited from StudentRequest
    // ? Currently no difference from parent, but keeping for future flexibility
}
