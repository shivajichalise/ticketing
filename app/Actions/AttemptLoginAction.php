<?php

declare(strict_types=1);

namespace App\Actions;

use App\Facades\Jwt;
use App\Models\RefreshToken;
use App\Models\User;
use Exception;
use Illuminate\Support\Facades\Hash;

final class AttemptLoginAction
{
    /**
     * Execute the action.
     */
    public function handle(array $fields): array
    {
        $user = User::where('email', $fields['email'])->first();
        if (! $user || ! Hash::check($fields['password'], $user->password)) {
            throw new Exception('Invalid email or password action.');
        }

        return $this->generateTokens($user);
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
