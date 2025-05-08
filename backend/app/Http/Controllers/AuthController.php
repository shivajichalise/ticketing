<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\AttemptLoginAction;
use App\Facades\AuditLogger;
use App\Facades\Jwt;
use App\Http\Requests\Auth\ChangePasswordRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Models\RefreshToken;
use App\Models\User;
use App\Traits\RespondsWithJson;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Throwable;

final class AuthController extends Controller
{
    use RespondsWithJson;

    public function register(RegisterRequest $request): JsonResponse
    {
        $fields = $request->only([
            'name',
            'email',
            'password',
            'phone',
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
                'INSERT INTO users (name, email, phone, password, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?)',
                [
                    $fields['name'],
                    $fields['email'],
                    $fields['phone'],
                    $fields['password'],
                    now(),
                    now(),
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
            ] = $action->handle($fields, $request->ip());

            $response = [
                'access_token' => $accessToken,
                // 'refresh_token' => $refreshToken,
                'user' => [
                    'id' => (string) $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                ],
            ];

            AuditLogger::log($user->id, 'login');

            return $this->success($response, 'Login successful')->cookie($this->refreshCookie($refreshToken));
        } catch (Throwable $th) {
            $statusCode = $th instanceof HttpExceptionInterface
                ? $th->getCode()
                : 500;

            return $this->error($th, $th->getMessage(), $statusCode);
        }
    }

    public function refresh(Request $request): JsonResponse
    {
        try {
            $cookieToken = $request->cookie('refresh_token');

            if (! $cookieToken) {
                return $this->error(new Exception('Missing refresh token'), 'Missing refresh token', 401);
            }

            $storedToken = RefreshToken::where('token', $cookieToken)->first();

            if (! $storedToken) {
                return $this->error(
                    new Exception('Refresh token is invalid'),
                    'Refresh token is invalud',
                    401,
                );
            }

            $payload = Jwt::verify($cookieToken, true);

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
            ], 'Token refreshed.')
                ->cookie($this->refreshCookie($refreshToken));
        } catch (Throwable $th) {
            return $this->error($th, 'Refresh token invalid or expired', 401);
        }
    }

    public function logout(Request $request): JsonResponse
    {
        $cookieToken = $request->cookie('refresh_token');

        RefreshToken::where('token', $cookieToken)->delete();

        AuditLogger::log($request->jwt_user_id, 'logout');

        return $this->success(
            [],
            'Logged out successfully',
            200,
        )
            ->cookie($this->forgetRefreshCookie());
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
            'phone' => $user->phone,
            'phone_details' => $user->phone_details,
        ]);
    }

    public function changePassword(ChangePasswordRequest $request): JsonResponse
    {
        $user = User::find($request->jwt_user_id);

        if (! Hash::check($request->old_password, $user->password)) {
            return $this->error(
                new Exception('The current password is incorrect'),
                'The current password is incorrect',
                422
            );
        }

        $user->update([
            'password' => Hash::make($request->password),
        ]);

        AuditLogger::log($user->id, 'password_change');

        return $this->success([], 'Password changed, successfully');
    }

    private function refreshCookie(string $token): Cookie
    {
        $isProduction = config('app.env') === 'production';
        $ttl = (int) config('jwt.refresh_ttl', 2);

        return Cookie::create(
            'refresh_token',
            $token,
            now()->addMinutes($ttl)
        )
            ->withPath('/')
            ->withSecure($isProduction ? true : false)            // set true in production for HTTPS
            ->withHttpOnly(true)
            ->withSameSite('Lax');
    }

    private function forgetRefreshCookie(): Cookie
    {
        $isProduction = config('app.env') === 'production';

        return Cookie::create('refresh_token')
            ->withValue('')
            ->withExpires(0)
            ->withPath('/')
            ->withSecure($isProduction ? true : false)
            ->withHttpOnly(true)
            ->withSameSite('Lax');
    }
}
