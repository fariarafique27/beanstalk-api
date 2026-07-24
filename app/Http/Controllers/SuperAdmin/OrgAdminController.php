namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Organization;
use App\Mail\AdminInviteMail;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;

class OrgAdminController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'org_name'     => 'required|string|max:255',
            'name'         => 'required|string|max:255',
            'email'        => 'required|email|unique:users,email',
            'password'     => 'nullable|string|min:8',
            'permissions'  => 'nullable|array',
            'send_email'   => 'nullable|boolean', // Toggle sending immediately or later
        ]);

        // 1. Create Organization
        $org = Organization::create([
            'name' => $request->org_name,
        ]);

        // 2. Generate Invite Token & Expiration (e.g., 45 mins)
        $token = Str::random(40);
        $expiresAt = now()->addMinutes(45); 

        // 3. Create Inactive Admin User
        $user = User::create([
            'name'                    => $request->name,
            'email'                   => $request->email,
            'role'                    => 'admin',
            'organization_id'         => $org->id,
            'permissions'             => $request->permissions ?? [],
            'invite_token'            => hash('sha256', $token),
            'invite_token_expires_at' => $expiresAt,
            'is_active'               => false,
            'password'                => $request->filled('password') ? $request->password : '123456789',
        ]);

        // 4. Optionally Send Email Now
        if ($request->boolean('send_email')) {
            $this->dispatchInviteEmail($user, $token);
        }

        return redirect()->back()->with('success', 'Admin created successfully.' . ($request->boolean('send_email') ? ' Invitation email dispatched.' : ' Invited stored (Email not sent).'));
    }

    public function resendInvite(Request $request, User $user)
    {
        // Regenerate fresh token valid for 45 minutes
        $token = Str::random(40);
        
        $user->update([
            'invite_token'            => hash('sha256', $token),
            'invite_token_expires_at' => now()->addMinutes(45),
            'invited_at'              => now(),
        ]);

        $this->dispatchInviteEmail($user, $token);

        return redirect()->back()->with('success', 'New invitation email sent! Link valid for 45 minutes.');
    }

    private function dispatchInviteEmail(User $user, string $plainToken)
    {
        // Generate a signed activation link
        $inviteUrl = URL::temporarySignedRoute(
            'invite.setup-password',
            $user->invite_token_expires_at,
            ['user' => $user->id, 'token' => $plainToken]
        );

        Mail::to($user->email)->send(new AdminInviteMail($user, $inviteUrl));

        $user->update(['invited_at' => now()]);
    }
}