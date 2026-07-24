<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\EmployeeController;
use App\Http\Controllers\Api\OrgAdminController; 
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\SetPasswordController;

// Public Auth Routes
Route::post('/login', [AuthController::class, 'login']);
Route::post('/set-password', [SetPasswordController::class, 'setPassword']);

// Protected Routes (Require Token)
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/me', [AuthController::class, 'me']);
    Route::post('/logout', [AuthController::class, 'logout']);

    // Organization & Admin Invitation Route
    Route::post('/org-admins/invite', [OrgAdminController::class, 'store']); 

    // Employee Management Routes (Permission Protected)
    Route::middleware('permission:employees.manage')->group(function () {
        Route::get('/employees', [EmployeeController::class, 'index']);
        Route::post('/employees', [EmployeeController::class, 'store']);
    });
});