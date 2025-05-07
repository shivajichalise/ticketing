<?php

namespace App\Traits;

use Illuminate\Http\JsonResponse;
use Throwable;

trait RespondsWithJson
{
    /**
     * Return a standardized JSON success response.
     */
    public function success(array $data = [], ?string $message = 'Request was successful.', int $statusCode = 200): JsonResponse
    {
        return response()->json([
            'status' => true,
            'message' => $message,
            'data' => $data,
        ], $statusCode);
    }

    /**
     * Return a standardized JSON error response.
     */
    public function error(Throwable $th, ?string $fallbackMessage = 'Something went wrong.', int $statusCode = 500): JsonResponse
    {
        $message = config('app.env') === 'production' ? $fallbackMessage : $th->getMessage();

        return response()->json([
            'status' => false,
            'message' => $message,
            'exception' => config('app.env') !== 'production' ? [
                'file' => $th->getFile(),
                'line' => $th->getLine(),
                'trace' => collect($th->getTrace())->take(5),
            ] : null,
        ], $statusCode);
    }
}
