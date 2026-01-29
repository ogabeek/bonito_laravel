<?php

namespace App\Services;

use Illuminate\Support\Facades\Hash;

/**
 * Handles password hashing and verification for authentication.
 */
class AuthenticationService
{
    /**
     * Verify a password against a hashed password.
     */
    public function verifyPassword(string $inputPassword, string $hashedPassword): bool
    {
        return Hash::check($inputPassword, $hashedPassword);
    }

    /**
     * Hash a password using bcrypt.
     */
    public function hash(string $password): string
    {
        return Hash::make($password);
    }
}
