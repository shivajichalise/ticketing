<?php

namespace App\Strategies\PasswordStrategies;

use App\Strategies\PasswordValidationStrategy;
use Illuminate\Support\Str;

/**
 * Check if the password contains dictionary word.
 */
class IsDictionaryWordStrategy implements PasswordValidationStrategy
{
    public function validate(string $password): ?string
    {
        $commonWords = [
            'password', 'welcome', 'admin', 'login', 'qwerty',
            'letmein', 'abc123', 'password123', '123456789',
        ];

        foreach ($commonWords as $word) {
            if (Str::contains(Str::lower($password), $word)) {
                return 'The password cannot contain common dictionary words.';
            }
        }

        return null;
    }
}
