    <?php

    use App\Http\Controllers\Api\AuthController;
    use App\Http\Controllers\Api\EmployeeController;
    use App\Http\Controllers\Api\OrgAdminController; 
    use Illuminate\Support\Facades\Route;
    use App\Http\Controllers\Api\SetPasswordController;
    use Spatie\Permission\Models\Permission;
    use App\Http\Controllers\Api\DashboardController;
    use App\Http\Controllers\Api\AttendanceController;
    use App\Http\Controllers\Api\DeviceController;

    // Public Auth Routes
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/set-password', [SetPasswordController::class, 'setPassword']);
    
    Route::get('/permissions', function () {
    return response()->json([
        'success' => true,
        'data' => Permission::all() // Returns all your 6-7 permissions from the database
            ]);
    });
        
    // Protected Routes (Require Token)
    Route::middleware('auth:sanctum')->group(function () {

        //1-Dashboard Routes
        Route::get('/super-admin/dashboard', [DashboardController::class, 'getSuperAdminDashboard']);               // Super Admin Dashboard (with your custom permission check)
        Route::get('/dashboard', [DashboardController::class, 'getDashboard']);                                      // Regular Company / Tenant Dashboard

        // Organization Management Routes
        Route::get('/organizations', [OrgAdminController::class, 'index']); // <-- ADDED THIS
        Route::put('/organizations/{id}', [OrgAdminController::class, 'updateOrgAdmin']); // <-- ADDED THIS
        Route::delete('/organizations/{id}', [OrgAdminController::class, 'destroyOrgAdmin']); // <-- ADDED THIS
        Route::post('/organizations/{id}/resend-invite', [OrgAdminController::class, 'resendInvite']); // <-- ADDED THIS


        Route::get('/attendances', [AttendanceController::class, 'index']);
        Route::get('/attendances/{id}', [AttendanceController::class, 'show']);
        
        //TODO ::::
        // Organization & Admin Invitation Route (Protected by permission)
        // Route::middleware('permission:org-admins.invite')->group(function () {
             Route::post('/org-admins/invite', [OrgAdminController::class, 'storeOrgAdmin']);
        // });

        // routes/api.php
        //Route::middleware('permission:device.manage')->group(function () {
            Route::get('/device', [DeviceController::class, 'show']);
            Route::post('/device', [DeviceController::class, 'store']);
            Route::post('/device/sync', [DeviceController::class, 'sync']);
        // });

        // Employee Management Routes (Permission Protected)
        Route::middleware('permission:employees.manage')->group(function () {
            Route::get('/employees', [EmployeeController::class, 'index']);
            Route::post('/employees', [EmployeeController::class, 'store']);
        });

        //Auth routes 
        Route::get('/me', [AuthController::class, 'me']);
        Route::post('/logout', [AuthController::class, 'logout']);
    });