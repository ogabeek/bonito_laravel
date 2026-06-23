<?php

namespace App\Services;

use Illuminate\Support\Facades\Hash;

class AuthenticationService
{
    public function verifyPassword(string $inputPassword, string $storedPassword): bool
    {
        if (password_get_info($storedPassword)['algoName'] !== 'unknown') {
            return Hash::check($inputPassword, $storedPassword);
        }

        return hash_equals($storedPassword, $inputPassword);
    }
}
