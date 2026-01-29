<?php

namespace App\Http\Requests;

/**
 * ! FORM REQUEST: Teacher Login Validation
 * * Purpose: Validates teacher login form data
 * * Why: Specific request class for teacher authentication
 * * What: Inherits all password validation from BaseLoginRequest
 * ? Kept separate for future teacher-specific rules (e.g., account verification)
 */
class TeacherLoginRequest extends BaseLoginRequest
{
    // * All validation logic inherited from BaseLoginRequest
    // ? If teachers need different rules later, override methods here
}
