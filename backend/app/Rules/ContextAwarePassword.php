<?php

declare(strict_types=1);

namespace App\Rules;

use App\Models\User;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;

final class ContextAwarePassword implements ValidationRule
{
    private User $user;

    public function __construct(private Request $request)
    {
        $this->user = User::find($request->jwt_user_id);
    }

    /**
     * Run the validation rule.
     *
     * @param  Closure(string, ?string=): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if ($this->containsPersonalInfo($value)) {
            $fail('The password cannot contain your name or email address.');

            return;
        }

        if ($this->isDictionaryWord($value)) {
            $fail('The password cannot contain common words.');

            return;
        }

        if ($this->isCommonPassword($value)) {
            $fail('The password cannot contain common words.');

            return;
        }

        if ($this->isSameAsCurrentPassword($value)) {
            $fail('The password must be different from your current password.');

            return;
        }

        if ($this->isPublicIp() && mb_strlen($value) < 10) {
            $fail('Your password must be at least 10 characters when using a public network.');

            return;
        }

        if ($this->isNightTime() && mb_strlen($value) < 12) {
            $fail('Your password must be at least 12 characters.');

            return;
        }

        if ($this->isStrongPassword($value)) {
            $fail('The password must contain at least one uppercase letter, one lowercase letter, one number, and one special character.');
        }
    }

    /**
     * Check if the password contains personal information like name or email.
     */
    private function containsPersonalInfo(string $password): bool
    {
        $lPassword = Str::lower($password);

        // full email check
        if ($this->user->email && Str::contains($lPassword, Str::lower($this->user->email))) {
            return true;
        }

        // remove domain from the check
        if ($this->user->email) {
            $email = Str::lower(explode('@', $this->user->email)[0]);
            if (Str::contains($lPassword, $email)) {
                return true;
            }
        }

        // check name
        if (Str::contains($lPassword, Str::lower($this->user->name))) {
            return true;
        }

        return false;
    }

    /**
     * Check if the password contains dictionary word.
     */
    private function isDictionaryWord(string $password): bool
    {
        $commonWords = [
            'password',
            'welcome',
            'admin',
            'login',
            'qwerty',
            'letmein',
            'abc123',
            'password123',
            '123456789',
        ];

        foreach ($commonWords as $word) {
            if (Str::contains(Str::lower($password), $word)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if the password contains common word.
     */
    private function isCommonPassword(string $password): bool
    {
        return ! Validator::make(
            ['password' => $password],
            ['password' => Password::min(6)->uncompromised()]
        )->passes();
    }

    /**
     * Check if the password is strong with mixed characeters.
     */
    private function isStrongPassword(string $password): bool
    {
        return ! Validator::make(
            ['password' => $password],
            [
                'password' => Password::min(8)
                    ->mixedCase()
                    ->letters()
                    ->numbers()
                    ->symbols(),
            ]
        )->passes();
    }

    /**
     * Prevent reusing the current password.
     */
    private function isSameAsCurrentPassword(string $password): bool
    {
        return password_verify($password, $this->user->password);
    }

    /**
     * Determine if the given IP address is a public (non-private, non-reserved) IP.
     *
     * FILTER_FLAG_NO_PRIV_RANGE - Excludes private IP ranges
     *
     * FILTER_FLAG_NO_RES_RANGE - Excludes reserved ranges
     */
    private function isPublicIp(): bool
    {
        $ip = $this->request->ip();

        return filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) !== false;
    }

    /**
     * Determine if its night time
     */
    private function isNightTime(): bool
    {
        $hour = now()->hour;

        return $hour < 6 || $hour >= 22;
    }
}
