<?php

declare(strict_types=1);

namespace App\Actions;

use App\Facades\Jwt;
use App\Models\RefreshToken;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;

final class AttemptLoginAction
{
    private int $freeAttempts = 3;

    private string $attemptKey;

    private string $lockKey;

    public function handle(array $fields, string $ip): array
    {
        $this->attemptKey = "login:attempts:{$fields['email']}-{$ip}";
        $this->lockKey = "login:lock:{$fields['email']}-{$ip}";

        if ($seconds = $this->secondsUntilUnlock()) {
            throw new TooManyRequestsHttpException(
                $seconds,
                "Too many attempts. Try again in {$seconds} seconds.",
                null,
                429
            );
        }

        $user = User::where('email', $fields['email'])->first();

        if (! $user || ! Hash::check($fields['password'], $user->password)) {
            $attempts = $this->incrementAttempts();

            if ($attempts > $this->freeAttempts) {
                $delay = min(60, pow(2, $attempts - $this->freeAttempts));
                $this->lockFor($delay);

                throw new TooManyRequestsHttpException(
                    $delay,
                    "Too many attempts. Try again in {$delay} seconds.",
                    null,
                    429
                );
            }

            $remaining = $this->freeAttempts - $attempts;

            if ($remaining > 1) {
                throw new UnauthorizedHttpException('Basic', "Invalid credentials. {$remaining} attempts left.", null, 401);
            }

            if ($remaining === 1) {
                throw new UnauthorizedHttpException('Basic', 'Invalid credentials. Last attempt remaining.', null, 401);
            }

            throw new UnauthorizedHttpException(
                'Basic',
                'Invalid credentials. Account will be locked on next failure.',
                null,
                401,
            );
        }

        Cache::forget($this->attemptKey);
        Cache::forget($this->lockKey);

        return $this->generateTokens($user);
    }

    private function incrementAttempts(): int
    {
        if (! Cache::has($this->attemptKey)) {
            Cache::put($this->attemptKey, 1, now()->addMinute());

            return 1;
        }

        return Cache::increment($this->attemptKey);
    }

    private function secondsUntilUnlock(): int
    {
        $unlockTimestamp = Cache::get($this->lockKey);

        if (! $unlockTimestamp) {
            return 0;
        }

        return max(1, $unlockTimestamp - time());
    }

    private function lockFor(int $seconds): void
    {
        Cache::put($this->lockKey, time() + $seconds, $seconds);
    }

    private function generateTokens(User $user): array
    {
        $accessToken = Jwt::sign(['sub' => $user->id]);
        $refreshToken = Jwt::sign(['sub' => $user->id], true);

        RefreshToken::create([
            'user_id' => $user->id,
            'token' => $refreshToken,
        ]);

        return [
            'user' => $user,
            'accessToken' => $accessToken,
            'refreshToken' => $refreshToken,
        ];
    }
}
