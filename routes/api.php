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
    use App\Http\Controllers\Api\UserController;

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
        Route::get('/organizations', [OrgAdminController::class, 'index']); 
        Route::put('/organizations/{id}', [OrgAdminController::class, 'updateOrgAdmin']); 
        Route::delete('/organizations/{id}', [OrgAdminController::class, 'destroyOrgAdmin']);
        Route::post('/organizations/{id}/resend-invite', [OrgAdminController::class, 'resendInvite']); 
                //TODO ::::
        // Organization & Admin Invitation Route (Protected by permission)
        // Route::middleware('permission:org-admins.invite')->group(function () {
             Route::post('/org-admins/invite', [OrgAdminController::class, 'storeOrgAdmin']);
        // });


        Route::get('/attendances', [AttendanceController::class, 'index']);
        Route::get('/attendances/{id}', [AttendanceController::class, 'show']);
        


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
        

        //USER MANAGEMENT ROUTES 
        Route::post('/users', [UserController::class, 'store'])->name('users.store');
        //TODO 
        // Route::get('/users', [UserController::class, 'index'])->name('users.index');
        // Route::get('/users/{id}', [UserController::class, 'show'])->name('users.show');
        // Route::put('/users/{id}', [UserController::class, 'update'])->name('users.update');
        // Route::delete('/users/{id}', [UserController::class, 'destroy'])->name('users.destroy');


        //Auth routes 
        Route::get('/me', [AuthController::class, 'me']);
        Route::post('/logout', [AuthController::class, 'logout']);
    });