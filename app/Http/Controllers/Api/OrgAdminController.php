<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Mail\OrgAdminInviteMail;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use App\Services\AuthService;
use Illuminate\Support\Facades\Log;
    
class OrgAdminController extends Controller
{

    protected $authService;

    // 2. Inject it via the constructor
    public function __construct(AuthService $authService)
    {
        $this->authService = $authService;
    }

    // public function storeOrgAdmin(Request $request)
    // {
    //     $validated = $request->validate([
    //         'name'     => 'required|string|max:255',
    //         'email'    => 'required|email|unique:users,email',
    //         'password' => 'nullable|string|min:8',
    //     ]);

    //     // 1. Create User
    //     $user = User::create([
    //         'name'     => $validated['name'],
    //         'email'    => $validated['email'],
    //         'password' => $request->filled('password') ? $request->password : '123456789',
    //     ]);

    //     // 2. Generate Password Reset Token
    //     $token = Password::createToken($user);

    //     // 3. Construct Frontend Set-Password URL
    //     // Replace localhost:3000 with your React frontend URL if needed
    //     // Construct the set-password URL with your exact host & port
    //     $setUrl = "http://127.0.0.1:8001/set-password?token={$token}&email=" . urlencode($user->email);

    //     // 4. Send Email with the link
    //     Mail::to($user->email)->send(new OrgAdminInviteMail($user, $setUrl));

    //     return response()->json([
    //         'message' => 'Organization admin invited successfully.',
    //         'data'    => $user,
    //     ], 201);
    // }

    public function storeOrgAdmin(Request $request)
    {
        // 1. Log that the request arrived
        Log::info('--- STORE ORG ADMIN REQUEST RECEIVED ---', [
            'all_data' => $request->all(),
            'permissions_from_request' => $request->input('permissions')
        ]);

        $validated = $request->validate([
            'name'     => 'required|string|max:255',
            'email'    => 'required|email|unique:users,email',
            'password' => 'nullable|string|min:8',
            'permissions' => 'nullable|array', 
            //'permissions.*' => 'string|exists:permissions,name',
        ]);

        try {
            // 2. Log before creating user
            Log::info('Attempting to create user record...');
            $user = User::create([
                'name'     => $validated['name'],
                'email'    => $validated['email'],
                'password' => $request->filled('password') ? $request->password : '123456789',
            ]);
            Log::info('User created successfully with ID: ' . $user->id);


            if ($request->has('permissions')) {
                $user->syncPermissions($request->permissions);
            }

            // 3. Generate Token
            Log::info('Generating password reset token...');
            $token = Password::createToken($user);
            Log::info('Token generated successfully.');

            // 4. Construct URL
            $setUrl = "http://127.0.0.1:8001/set-password?token={$token}&email=" . urlencode($user->email);
            Log::info('Set URL constructed: ' . $setUrl);

            // 5. Attempt to send email
            Log::info('Triggering Mail::to()->send() for: ' . $user->email);
            Mail::to($user->email)->send(new OrgAdminInviteMail($user, $setUrl));
            Log::info('Mail::to()->send() executed successfully without throwing an exception.');

            return response()->json([
                'success' => true,
                'message' => 'Organization admin invited successfully.',
                'data'    => $user,
            ], 201);

        } catch (\Exception $e) {
            // Catch any errors and write them directly to the log file
            Log::error('CRITICAL ERROR in storeOrgAdmin: ' . $e->getMessage());
            Log::error($e->getTraceAsString());

            return response()->json([
                'success' => false,
                'message' => 'An error occurred: ' . $e->getMessage()
            ], 500);
        }
    }

    // API Endpoint: Return list of organizations and stats
public function index()
    {
        // Fetch users/organizations safely without hardcoding missing columns
        $organizations = User::all()->map(function ($user) {
            return [
                'id' => $user->id,
                'org_name' => $user->org_name ?? $user->name, // Fallback if column name differs
                'admin_name' => $user->name,
                'admin_email' => $user->email,
                'status' => $user->status ?? 'active', // Safe fallback if column doesn't exist yet
                'permissions' => $user->permissions ?? ['Standard'],
            ];
        });

        $stats = [
            'total_orgs' => $organizations->count(),
            'active_admins' => collect($organizations)->where('status', 'active')->count(),
            'pending_invites' => collect($organizations)->where('status', '!=', 'active')->count(),
        ];

        return response()->json([
            'success' => true,
            'data' => [
                'organizations' => $organizations,
                'stats' => $stats
            ]
        ], 200);
    }

}