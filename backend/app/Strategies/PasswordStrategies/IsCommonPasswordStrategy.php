<?php

namespace App\Strategies\PasswordStrategies;

use App\Strategies\PasswordValidationStrategy;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;

/**
 * Check if the password contains common word.
 */
class IsCommonPasswordStrategy implements PasswordValidationStrategy
{
    public function validate(string $password): ?string
    {
        $valid = Validator::make(
            ['password' => $password],
            ['password' => Password::min(6)->uncompromised()]
        )->passes();

        return $valid ? null : 'The password has been found in known data breaches. Choose another.';
    }
}
