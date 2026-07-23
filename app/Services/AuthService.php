<?php

namespace App\Services;

use App\Http\Resources\ResponseUser;
use App\Models\OrganizationIndex;
use App\Repositories\AuthRepository;
use Illuminate\Support\Facades\Hash;
use App\Repositories\BaseRepository;
use App\Services\SalaryEncryptionService;

class AuthService extends BaseService
{
    public function __construct(
        protected AuthRepository $authRepository
    ) {}

    public function login($request)
    {
        $loginUserResponse = $this->authRepository->fetchUser($request);

        if (! $loginUserResponse) {
            return $this->errorResponse($this->getMessageData('error', 'en')['invalid_error'], 401);
        }

        if ($loginUserResponse->password == null) {
            return $this->errorResponse($this->getMessageData('error', 'en')['password_not_set_contact_admin'], 401);
        }

        // Check if user's organization is deleted or inactive before issuing token
        if ($loginUserResponse->organization && $loginUserResponse->organization->trashed()) {
            return $this->errorResponse($this->getMessageData('error', 'en')['organization_deleted'], 403);
        }

        if ($loginUserResponse->organization && (int) $loginUserResponse->organization->status === 0) {
            return $this->errorResponse($this->getMessageData('error', 'en')['organization_inactive'], 403);
        }

        // If user is an employee, allow login only when employee.status == 1 and user is not root
        if ($loginUserResponse->employee && ! $loginUserResponse->is_root) {
            if ((int) ($loginUserResponse->employee->status ?? 0) !== 1) {
                return $this->errorResponse($this->getMessageData('error', 'en')['employee_account_inactive_con_admin'], 403);
            }
        }

        if (! Hash::check($request->password, $loginUserResponse->password)) {
            return $this->errorResponse($this->getMessageData('error', 'en')['invalid_error'], 401);
        }

        $index_name = OrganizationIndex::where('organization_id', $loginUserResponse->organization?->id)->value('index_name');
        $chatbot_status = OrganizationIndex::where('organization_id', $loginUserResponse->organization?->id)->value('is_active');

        // Revoke all existing tokens to logout from other locations
        $loginUserResponse->tokens()->delete();

        $token = $loginUserResponse->createToken('Hrms')->plainTextToken;

        // Revoke decrypt token if present
        $decryptionTokenKey = $loginUserResponse->cache_key;

        if ($decryptionTokenKey) {
            app(\App\Services\SalaryEncryptionService::class)->revokeDecryptToken($decryptionTokenKey, $loginUserResponse->id);
        }

        return (new ResponseUser($loginUserResponse))->prepareUserResponse($loginUserResponse, $token, $index_name, $chatbot_status);
    }

    public function register($request)
    {
        $user = $this->authRepository->registerUser($request->all());

        $token = $user->createToken('Hrms')->plainTextToken;

        return (new ResponseUser($user))->prepareUserResponse($user, $token, null, null);
    }

    public function me($request)
    {
        $user = $this->authRepository->findUserById($request->user()->id);

        return $this->successResponse($user, $this->getMessageData('success', 'en')['fetch_success']);
    }

    public function logout($request)
    {
        $this->authRepository->logout($request);

        return $this->successResponse(null, $this->getMessageData('success', 'en')['logout_success']);
    }
}