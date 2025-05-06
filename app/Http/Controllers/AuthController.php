<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\AttemptLoginAction;
use App\Facades\Jwt;
use App\Models\RefreshToken;
use App\Models\User;
use App\Traits\RespondsWithJson;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Throwable;

final class AuthController extends Controller
{
    use RespondsWithJson;

    public function register(Request $request): JsonResponse
    {
        $fields = $request->validate([
            'name' => ['required', 'string', 'max:225'],
            'email' => ['required', 'email:rfc', Rule::unique('users', 'email')],
            'password' => ['required', 'confirmed'],
        ]);

        $fields['password'] = Hash::make($fields['password']);

        try {
            // sql injection prone code
            // DB::statement("
            //     INSERT INTO users (name, email, password, created_at, updated_at)
            //     VALUES ('{$fields['name']}', '{$fields['email']}', '{$fields['password']}', NOW(), NOW())
            // ");

            // prevent sql injection
            DB::insert(
                'INSERT INTO users (name, email, password, created_at, updated_at) VALUES (?, ?, ?, NOW(), NOW())',
                [
                    $fields['name'],
                    $fields['email'],
                    $fields['password'],
                ]
            );

            return $this->success(
                [],
                'Registration successful',
                201,
            );
        } catch (Throwable $th) {
            return $this->error(
                $th,
                'Registration failed',
            );
        }
    }

    public function login(Request $request, AttemptLoginAction $action): JsonResponse
    {
        $fields = $request->validate([
            'email' => ['required', 'email:rfc'],
            'password' => ['required', 'string'],
        ]);

        try {
            [
                'user' => $user,
                'accessToken' => $accessToken,
                'refreshToken' => $refreshToken
            ] = $action->handle($fields);

            $response = [
                'access_token' => $accessToken,
                'refresh_token' => $refreshToken,
                'user' => [
                    'id' => (string) $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                ],
            ];

            return $this->success($response, 'Login successful');
        } catch (Throwable $th) {
            return $this->error($th, 'Login failed');
        }
    }

    public function refresh(Request $request): JsonResponse
    {
        $request->validate([
            'refresh_token' => ['required', 'string'],
        ]);

        try {
            $storedToken = RefreshToken::where('token', $request->refresh_token)->first();

            if (! $storedToken) {
                return $this->error(
                    new Exception('Refresh token is invalid'),
                    'Refresh token is invalud',
                    401,
                );
            }

            $payload = Jwt::verify($request->refresh_token, true);

            $user = User::find($payload['sub']);

            if (! $user) {
                return $this->error(
                    new Exception('User not found'),
                    'User not found',
                    404,
                );
            }

            $accessToken = Jwt::sign(['sub' => $user->id]);
            $refreshToken = Jwt::sign(['sub' => $user->id], isRefresh: true);

            $storedToken->delete();

            RefreshToken::create([
                'user_id' => $user->id,
                'token' => $refreshToken,
            ]);

            return $this->success([
                'access_token' => $accessToken,
                'refresh_token' => $refreshToken,
            ], 'Token refreshed.');
        } catch (Throwable $th) {
            return $this->error($th, 'Refresh token invalid or expired', 401);
        }
    }

    public function logout(Request $request): JsonResponse
    {
        RefreshToken::where('user_id', $request->jwt_user_id)
            ->delete();

        return $this->success(
            [],
            'Logged out successfully',
            200,
        );
    }

    public function me(Request $request): JsonResponse
    {
        $user = User::find($request->jwt_user_id);

        if (! $user) {
            return $this->error(
                new Exception('User not found'),
                'User not found',
                404
            );
        }

        return $this->success([
            'id' => (string) $user->id,
            'name' => $user->name,
            'email' => $user->email,
        ]);
    }
}
