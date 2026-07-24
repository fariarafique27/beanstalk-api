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

class OrgAdminController extends Controller
{
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'     => 'required|string|max:255',
            'email'    => 'required|email|unique:users,email',
            'password' => 'nullable|string|min:8',
        ]);

        // 1. Create User
        $user = User::create([
            'name'     => $validated['name'],
            'email'    => $validated['email'],
            'password' => $request->filled('password') ? $request->password : '123456789',
        ]);

        // 2. Generate Password Reset Token
        $token = Password::createToken($user);

        // 3. Construct Frontend Set-Password URL
        // Replace localhost:3000 with your React frontend URL if needed
        // Construct the set-password URL with your exact host & port
        $setUrl = "http://127.0.0.1:8001/set-password?token={$token}&email=" . urlencode($user->email);

        // 4. Send Email with the link
        Mail::to($user->email)->send(new OrgAdminInviteMail($user, $setUrl));

        return response()->json([
            'message' => 'Organization admin invited successfully.',
            'data'    => $user,
        ], 201);
    }
}