<?php

namespace App\Traits;

use Illuminate\Http\JsonResponse;

trait ApiResponse
{
    /**
     * if the request is successful, return a unified success response (Success Response)
     */
    protected function apiResponse(mixed $data = null, string $message = 'Success', int $statusCode = 200): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $data,
        ], $statusCode);
    }

    /**
     * if there is an error, return a unified error response (Error Response)
     */
    protected function errorResponse(string $message = 'Error occurred', int $statusCode = 400): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $message,
            'data' => null,
        ], $statusCode);
    }
}
