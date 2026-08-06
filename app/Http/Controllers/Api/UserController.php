<?php

namespace App\Http\Controllers\Api;

use App\Services\UserService;
use App\Http\Requests\CreateUserRequest;
use App\Http\Controllers\Controller; 

class UserController extends Controller
{
    protected UserService $userService;

    public function __construct(UserService $userService)
    {
        $this->userService = $userService;
    }

    /**
     * Store a new user
     * 
     * @param CreateUserRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(CreateUserRequest $request)
    {
        try {
            return $this->userService->createUser($request->validated());

        } catch (\Exception $e) {
            $this->storeException($e);
            return $this->errorResponse(
                $this->getMessageData('error', 'en')['general_error'],
                500
            );
        }
    }
}