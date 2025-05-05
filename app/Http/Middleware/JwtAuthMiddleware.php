<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Facades\Jwt;
use App\Traits\RespondsWithJson;
use Closure;
use Exception;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class JwtAuthMiddleware
{
    use RespondsWithJson;

    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $authHeader = $request->header('Authorization');

        if (! $authHeader || ! str_starts_with($authHeader, 'Bearer ')) {
            return $this->error(
                new Exception('Authorization token missing'),
                'Authorization token missing',
                401
            );
        }

        $token = mb_substr($authHeader, 7);

        try {
            $payload = Jwt::verify($token);

            $request->merge(['jwt_user_id' => $payload['sub']]);

            return $next($request);
        } catch (Exception $e) {
            return $this->error(
                $e,
                $e->getMessage(),
                401
            );
        }
    }
}
