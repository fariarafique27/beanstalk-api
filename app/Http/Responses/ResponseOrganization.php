<?php

namespace App\Http\Responses;

use Illuminate\Http\Resources\Json\JsonResource;

class ResponseOrganization extends JsonResource
{
    public function prepareOrganizationResponse($organizations, $stats): array
    {
        return [
            'success' => true,
            'message' => 'Organizations retrieved successfully',
            'data' => [
                'organizations' => $organizations,
                'stats' => $stats
            ]
        ];
    }
}