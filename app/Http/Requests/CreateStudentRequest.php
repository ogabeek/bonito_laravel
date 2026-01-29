<?php

namespace App\Http\Requests;

/**
 * ! FORM REQUEST: Create Student
 * * Purpose: Validates data when creating a new student
 * * Why: Separate class for semantic clarity and future extensibility
 * * What: Currently uses all parent StudentRequest rules
 *
 * ? Why keep this instead of using StudentRequest directly?
 * - Type hinting in controller shows intent: "This creates students"
 * - Easy to add create-specific rules later (e.g., check for duplicate names/emails)
 * - Follows Laravel convention: separate requests for create/update operations
 *
 * TODO: Consider adding unique email validation if emails become required
 */
class CreateStudentRequest extends StudentRequest
{
    // * Authorization and validation rules inherited from StudentRequest
    // ? Currently no difference from parent, but keeping for future flexibility
}
