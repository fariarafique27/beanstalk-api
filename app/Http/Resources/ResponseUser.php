<?php

namespace App\Http\Resources;

use App\Models\OrganizationPermission;
use Illuminate\Http\Resources\Json\JsonResource;

class ResponseUser extends JsonResource
{
    /**
     * Format user response payload for API authentication.
     */
    public function prepareUserResponse($user, $token = null, $indexName = null, $chatbotStatus = null): array
    {
        // 1. Fetch Organization Module Permissions if user belongs to an org
        $orgPermissions = [];
        if ($user->organization_id) {
            $orgPermissions = OrganizationPermission::where('organization_id', $user->organization_id)
                ->pluck('module_key')
                ->toArray();
        }

        // 2. Fetch standard Spatie permissions
        $spatiePermissions = $user->getAllPermissions()->pluck('name')->toArray();

        // 3. Combine both lists (unique values)
        $allPermissions = array_unique(array_merge($spatiePermissions, $orgPermissions));

        logger('RESPONSE USER PERMISSIONS CHECK', [
        'user_id' => $user->id,
        'email' => $user->email,
        'is_root' => $user->is_root,
        'roles' => $user->getRoleNames(),
        'spatie_permissions' => $spatiePermissions,
        'final_permissions' => $allPermissions
    ]);
    

        return [
            'id'             => $user->id,
            'name'           => $user->name,
            'email'          => $user->email,
            'is_root'        => (bool) $user->is_root,
            'organization'   => $user->organization,
            'roles'          => $user->getRoleNames(),
            'permissions'    => array_values($allPermissions), // Contains both Spatie & Org permissions
            'token'          => $token,
            'index_name'     => $indexName,
            'chatbot_status' => $chatbotStatus,
        ];
    }
}