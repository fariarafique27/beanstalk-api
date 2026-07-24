namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;

class AcceptInviteController extends Controller
{
    public function showSetupForm(Request $request, User $user, $token)
    {
        // Verify token validity and expiration window (30-60 min)
        if (
            hash('sha256', $token) !== $user->invite_token ||
            now()->greaterThan($user->invite_token_expires_at)
        ) {
            return view('auth.invite-expired', ['user' => $user]);
        }

        return view('auth.setup-password', [
            'user' => $user,
            'token' => $token
        ]);
    }

    public function processPasswordSetup(Request $request, User $user, $token)
    {
        if (
            hash('sha256', $token) !== $user->invite_token ||
            now()->greaterThan($user->invite_token_expires_at)
        ) {
            return redirect()->route('login')->with('error', 'This invitation link has expired. Please contact your Super Admin for a new link.');
        }

        $request->validate([
            'password' => 'required|string|min:8|confirmed',
        ]);

        // Activate user and wipe token
        $user->update([
            'password'                => Hash::make($request->password),
            'is_active'               => true,
            'invite_token'            => null,
            'invite_token_expires_at' => null,
            'email_verified_at'       => now(),
        ]);

        Auth::login($user);

        return redirect()->route('admin.dashboard')->with('success', 'Account set up successfully!');
    }
}