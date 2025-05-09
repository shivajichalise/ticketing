<?php

namespace App\Strategies\PasswordStrategies;

use App\Models\User;
use App\Strategies\PasswordValidationStrategy;

/**
 * Prevent reusing the current password.
 */
class IsSameAsCurrentPasswordStrategy implements PasswordValidationStrategy
{
    public function __construct(private ?User $user) {}

    public function validate(string $password): ?string
    {
        if (! $this->user) {
            return null;
        }

        return password_verify($password, $this->user->password)
            ? 'The password must be different from your current password.'
            : null;
    }
}
