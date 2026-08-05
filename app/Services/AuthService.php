<?php

namespace App\Services;

use App\Http\Resources\ResponseUser;
use App\Models\OrganizationIndex;
use App\Repositories\AuthRepository;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class AuthService extends BaseService
{
    protected AuthRepository $authRepository;

    public function __construct(AuthRepository $authRepository)
    {
        $this->authRepository = $authRepository;
    }
    
     //use
    public function login($request)
    {

        $loginUserResponse = $this->authRepository->fetchUser($request);

        if (! $loginUserResponse) {
            return $this->errorResponse($this->getMessageData('error', 'en')['invalid_error'], 401);
        }

        if ($loginUserResponse->password == null) {
            return $this->errorResponse($this->getMessageData('error', 'en')['password_not_set_contact_admin'], 401);
        }

        if (! $loginUserResponse->is_root) {
            if ($loginUserResponse->organization && $loginUserResponse->organization->trashed()) {
                return $this->errorResponse($this->getMessageData('error', 'en')['organization_deleted'], 403);
            }

        }

        if (! Hash::check($request->password, $loginUserResponse->password)) {
            return $this->errorResponse($this->getMessageData('error', 'en')['invalid_error'], 401);
        }

       // $index_name = OrganizationIndex::where('organization_id', $loginUserResponse->organization?->id)->value('index_name');
      //  $chatbot_status = OrganizationIndex::where('organization_id', $loginUserResponse->organization?->id)->value('is_active');

        // Revoke all existing tokens to logout from other locations
        $loginUserResponse->tokens()->delete();

        $token = $loginUserResponse->createToken('Hrms')->plainTextToken;

        $permissions = $this->authRepository->getUserPermissions($loginUserResponse);


        return (new ResponseUser($loginUserResponse))->prepareUserResponse($loginUserResponse, $token ,  $permissions  );
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

        //return $this->successResponse($user, $this->getMessageData('success', 'en')['fetch_success']);
    }

    public function logout($request)
    {
        $this->authRepository->logout($request);

        //return $this->successResponse(null, $this->getMessageData('success', 'en')['logout_success']);
    }
}