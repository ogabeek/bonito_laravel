<?php

namespace App\Services;

use Illuminate\Support\Facades\Hash;

/**
 * * SERVICE: Password hashing wrapper
 * ? Why wrapper? Allows easy testing via mocking and centralizes auth logic
 */
class AuthenticationService
{
    /**
     * * Hash::check() compares plaintext against bcrypt hash
     */
    public function verifyPassword(string $inputPassword, string $hashedPassword): bool
    {
        return Hash::check($inputPassword, $hashedPassword);
    }

    /**
     * * Hash::make() uses bcrypt by default (configurable in config/hashing.php)
     */
    public function hash(string $password): string
    {
        return Hash::make($password);
    }
}
