<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

abstract class Controller
{
    /**
     * Store and log exceptions safely.
     */
    public function storeException(\Throwable $e): void
    {
        Log::error($e->getMessage(), [
            'file'  => $e->getFile(),
            'line'  => $e->getLine(),
            'trace' => $e->getTraceAsString(),
        ]);
    }

    /**
     * Return standardized JSON error response.
     */
    public function errorResponse(string $message, int $code = 400, mixed $data = null): JsonResponse
    {
        return response()->json([
            'status'  => false,
            'message' => $message,
            'data'    => $data,
        ], $code);
    }

    /**
     * Get system message dictionaries for internationalization.
     */
    public function getMessageData(string $type = 'error', string $lang = 'en'): array
    {
        return [
            'general_error' => 'Something went wrong. Please try again later.',
            'invalid_error' => 'Invalid credentials provided.',
            'logout_success' => 'Logged out successfully.',
            'fetch_success' => 'Data retrieved successfully.',
            
        ];
    }
}