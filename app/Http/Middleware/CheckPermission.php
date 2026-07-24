<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckPermission
{
    public function handle(Request $request, Closure $next, string $permission): Response
    {
        $user = $request->user();

        // Super Admin bypasses checks
        if ($user && $user->role === 'super_admin') {
            return $next($request);
        }

        // Parse permissions array/JSON
        $permissions = is_array($user->permissions) 
            ? $user->permissions 
            : json_decode($user->permissions ?? '[]', true);

        if ($user && $user->is_active && in_array($permission, $permissions ?? [])) {
            return $next($request);
        }

        return response()->json(['message' => 'Unauthorized action.'], 403);
    }
}