<?php

namespace App\Strategies\PasswordStrategies;

use App\Strategies\PasswordValidationStrategy;
use Carbon\Carbon;

/**
 * Determine if its night time
 */
class NightTimeLengthStrategy implements PasswordValidationStrategy
{
    public function validate(string $password): ?string
    {
        $hour = Carbon::now(config('app.timezone'))->hour;

        return (($hour < 6 || $hour >= 22) && mb_strlen($password) < 12)
            ? 'Your password must be at least 12 characters during nighttime.'
            : null;
    }
}
