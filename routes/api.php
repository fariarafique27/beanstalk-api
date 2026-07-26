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

    // Organization Management Routes
    Route::get('/organizations', [OrgAdminController::class, 'index']); // <-- ADDED THIS
    Route::put('/organizations/{id}', [OrgAdminController::class, 'updateOrgAdmin']); // <-- ADDED THIS
    Route::delete('/organizations/{id}', [OrgAdminController::class, 'destroyOrgAdmin']); // <-- ADDED THIS
    Route::post('/organizations/{id}/resend-invite', [OrgAdminController::class, 'resendInvite']); // <-- ADDED THIS

    // Organization & Admin Invitation Route (Protected by permission)
    Route::middleware('permission:org-admins.invite')->group(function () {
        Route::post('/org-admins/invite', [OrgAdminController::class, 'store']); 
    });

    // Employee Management Routes (Permission Protected)
    Route::middleware('permission:employees.manage')->group(function () {
        Route::get('/employees', [EmployeeController::class, 'index']);
        Route::post('/employees', [EmployeeController::class, 'store']);
    });
});