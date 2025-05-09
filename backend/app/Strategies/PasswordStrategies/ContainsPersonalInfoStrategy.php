<?php

namespace App\Strategies\PasswordStrategies;

use App\Models\User;
use App\Strategies\PasswordValidationStrategy;
use Illuminate\Support\Str;

/**
 * Check if the password contains personal information like name or email.
 */
class ContainsPersonalInfoStrategy implements PasswordValidationStrategy
{
    public function __construct(private ?User $user) {}

    public function validate(string $password): ?string
    {
        if (! $this->user) {
            return null;
        }

        $lPassword = Str::lower($password);

        if ($this->user->email && Str::contains($lPassword, Str::lower($this->user->email))) {
            return 'The password cannot contain your email address.';
        }

        if ($this->user->email) {
            $email = Str::lower(explode('@', $this->user->email)[0]);
            if (Str::contains($lPassword, $email)) {
                return 'The password cannot contain part of your email address.';
            }
        }

        if (Str::contains($lPassword, Str::lower($this->user->name))) {
            return 'The password cannot contain your name.';
        }

        return null;
    }
}
