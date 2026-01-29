<?php

namespace App\Http\Requests;

/**
 * ! FORM REQUEST: Admin Login Validation
 * * Purpose: Validates admin login form data
 * * Why: Specific request class for admin authentication
 * * What: Inherits all password validation from BaseLoginRequest
 * ? Kept separate for future admin-specific rules (e.g., 2FA, rate limiting)
 */
class AdminLoginRequest extends BaseLoginRequest
{
    // * All validation logic inherited from BaseLoginRequest
    // ? If admins need different rules later, override methods here
}
