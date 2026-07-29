    <?php

    use App\Http\Controllers\Api\AuthController;
    use App\Http\Controllers\Api\EmployeeController;
    use App\Http\Controllers\Api\OrgAdminController; 
    use Illuminate\Support\Facades\Route;
    use App\Http\Controllers\Api\SetPasswordController;
    use Spatie\Permission\Models\Permission;
    use App\Http\Controllers\Api\DashboardController;

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

        // Super Admin Dashboard (with your custom permission check)
        // Route::get('/super-admin/dashboard', [DashboardController::class, 'getSuperAdminDashboard'])
        //     ->middleware('permission:organization.read');
Route::get('/super-admin/dashboard', function (\Illuminate\Http\Request $request) {
    logger('GUARD CHECK DEBUG:', [
        'guard_name' => $request->user()?->guard_name,
        'can_read' => $request->user()?->can('organization.read'),
        'has_direct_permission' => $request->user()?->hasPermissionTo('organization.read'),
    ]);
    
    // Resolve via app container so constructor injection works automatically:
    return app(DashboardController::class)->getSuperAdminDashboard();
});

        // Regular Company / Tenant Dashboard
        Route::get('/dashboard', [DashboardController::class, 'getDashboard']);

        Route::get('/me', [AuthController::class, 'me']);
        Route::post('/logout', [AuthController::class, 'logout']);

        // Organization Management Routes
        Route::get('/organizations', [OrgAdminController::class, 'index']); // <-- ADDED THIS
        Route::put('/organizations/{id}', [OrgAdminController::class, 'updateOrgAdmin']); // <-- ADDED THIS
        Route::delete('/organizations/{id}', [OrgAdminController::class, 'destroyOrgAdmin']); // <-- ADDED THIS
        Route::post('/organizations/{id}/resend-invite', [OrgAdminController::class, 'resendInvite']); // <-- ADDED THIS


        
        //TODO ::::
        // Organization & Admin Invitation Route (Protected by permission)
        // Route::middleware('permission:org-admins.invite')->group(function () {
             Route::post('/org-admins/invite', [OrgAdminController::class, 'storeOrgAdmin']);
        // });

        // Employee Management Routes (Permission Protected)
        Route::middleware('permission:employees.manage')->group(function () {
            Route::get('/employees', [EmployeeController::class, 'index']);
            Route::post('/employees', [EmployeeController::class, 'store']);
        });
    });