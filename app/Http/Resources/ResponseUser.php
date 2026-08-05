<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ResponseUser extends JsonResource
{
    public function prepareUserResponse($user, $token = null, array $permissions = []): array
    {
        return [
            'id'           => $user->id,
            'name'         => $user->name,
            'email'        => $user->email,
            'is_root'      => (bool) $user->is_root,
            'organization' => $user->organization,
            'roles'        => $user->getRoleNames(),
            'permissions'  => $permissions,
            'token'        => $token,
        ];
    }
}