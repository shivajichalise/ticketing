<?php

namespace App\Strategies\PasswordStrategies;

use App\Strategies\PasswordValidationStrategy;
use Illuminate\Http\Request;

/**
 * Determine if the given IP address is a public (non-private, non-reserved) IP.
 *
 * FILTER_FLAG_NO_PRIV_RANGE - Excludes private IP ranges
 *
 * FILTER_FLAG_NO_RES_RANGE - Excludes reserved ranges
 */
class PublicIpLengthStrategy implements PasswordValidationStrategy
{
    public function __construct(private Request $request) {}

    public function validate(string $password): ?string
    {
        $ip = $this->request->ip();
        $isPublic = filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE);

        return ($isPublic && mb_strlen($password) < 10)
            ? 'Your password must be at least 10 characters when using a public network.'
            : null;
    }
}
