<?php

namespace App\Strategies\PasswordStrategies;

use App\Strategies\PasswordValidationStrategy;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;

/**
 * Check if the password is strong with mixed characters.
 */
class StrongPasswordFormatStrategy implements PasswordValidationStrategy
{
    public function validate(string $password): ?string
    {
        $valid = Validator::make(
            ['password' => $password],
            ['password' => Password::min(8)->mixedCase()->letters()->numbers()->symbols()]
        )->passes();

        return $valid
            ? null
            : 'Your password must be at least 8 characters long and include uppercase and lowercase letters, numbers, and special symbols.';
    }
}
