<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\LoginUserRequest;
use App\Http\Requests\RegisterUserRequest;
use App\Services\AuthService;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    protected $authService;

    public function __construct(AuthService $authService)
    {
        $this->authService = $authService;
    }

     //use
    public function login(LoginUserRequest $request)
    {
        try {
            return $this->authService->login($request);
        } catch (\Exception $e) {
            $this->storeException($e);
            return $this->errorResponse($this->getMessageData('error', 'en')['general_error'], 500);
        }
    }

    public function register(RegisterUserRequest $request)
    {
        try {
            return $this->authService->register($request);
        } catch (\Exception $e) {
            $this->storeException($e);
            return $this->errorResponse($this->getMessageData('error', 'en')['general_error'], 500);
        }
    }

    public function me(Request $request)
    {
        try {
            return $this->authService->me($request);
        } catch (\Exception $e) {
            $this->storeException($e);
            return $this->errorResponse($this->getMessageData('error', 'en')['general_error'], 500);
        }
    }

    public function logout(Request $request)
    {
        try {
            return $this->authService->logout($request);
        } catch (\Exception $e) {
            $this->storeException($e);
            return $this->errorResponse($this->getMessageData('error', 'en')['general_error'], 500);
        }
    }
}