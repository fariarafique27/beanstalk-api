<?php

namespace App\Services;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

abstract class BaseService
{
    /**
     * Standardized Error Response Format
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
     * Standardized Success Response Format
     */
    public function successResponse(mixed $data = null, string $message = 'Success', int $code = 200): JsonResponse
    {
        return response()->json([
            'status'  => true,
            'message' => $message,
            'data'    => $data,
        ], $code);
    }

    /**
     * System Message Lookup (Languages/Translations)
     */
    public function getMessageData(string $type = 'error', string $lang = 'en'): array
    {
        // Add your localized error messages here
        return [
            'invalid_error' => 'Invalid email or password.',
            'password_not_set_contact_admin' => 'Your password is not set. Please contact system admin.',
            'organization_deleted' => 'Your organization account has been deleted.',
            'organization_inactive' => 'Your organization account is inactive. Please contact support.',
            'employee_account_inactive_con_admin' => 'Your employee account is inactive. Please contact admin.',
            'general_error' => 'Something went wrong. Please try again later.',
            'logout_success' => 'Successfully logged out.',
            'fetch_success' => 'Data retrieved successfully.',
        ];
    }
}