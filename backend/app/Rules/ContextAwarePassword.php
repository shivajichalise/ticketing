<?php

declare(strict_types=1);

namespace App\Rules;

use App\Models\User;
use App\Strategies\PasswordStrategies\ContainsPersonalInfoStrategy;
use App\Strategies\PasswordStrategies\IsCommonPasswordStrategy;
use App\Strategies\PasswordStrategies\IsDictionaryWordStrategy;
use App\Strategies\PasswordStrategies\IsSameAsCurrentPasswordStrategy;
use App\Strategies\PasswordStrategies\NightTimeLengthStrategy;
use App\Strategies\PasswordStrategies\PublicIpLengthStrategy;
use App\Strategies\PasswordStrategies\StrongPasswordFormatStrategy;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Http\Request;

final class ContextAwarePassword implements ValidationRule
{
    /** @var PasswordValidationStrategy[] */
    private array $strategies;

    public function __construct(private Request $request)
    {
        $user = User::find($request->jwt_user_id);

        $this->strategies = [
            new ContainsPersonalInfoStrategy($user),
            new IsDictionaryWordStrategy,
            new IsCommonPasswordStrategy,
            new IsSameAsCurrentPasswordStrategy($user),
            new PublicIpLengthStrategy($request),
            new NightTimeLengthStrategy,
            new StrongPasswordFormatStrategy,
        ];
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        foreach ($this->strategies as $strategy) {
            $message = $strategy->validate($value);
            if ($message !== null) {
                $fail($message);

                return;
            }
        }
    }
}
