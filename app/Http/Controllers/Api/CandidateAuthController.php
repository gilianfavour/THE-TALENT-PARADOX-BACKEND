<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Candidate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;

/**
 * Candidate accounts: register, sign in and manage a password.
 * A successful sign-in returns the candidate's private portal token, which the
 * frontend keeps in an httpOnly session cookie.
 */
class CandidateAuthController extends Controller
{
    private function session(Candidate $c): array
    {
        return [
            'token' => $c->portal_token,
            'name' => $c->name,
            'email' => $c->email,
            'has_password' => (bool) $c->password,
        ];
    }

    public function register(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'password' => ['required', 'confirmed', Password::min(8)],
            'phone' => 'nullable|string|max:50',
            'location' => 'nullable|string|max:255',
        ]);

        $existing = Candidate::where('email', $data['email'])->first();
        if ($existing) {
            return response()->json([
                'message' => $existing->password
                    ? 'An account already exists for this email. Sign in instead.'
                    : 'You have applied with this email before. Open the portal link from your application and set a password under Settings, then sign in.',
            ], 409);
        }

        $candidate = Candidate::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => $data['password'],
            'phone' => $data['phone'] ?? null,
            'location' => $data['location'] ?? null,
            'portal_token' => Str::random(40),
            'status' => 'active',
            'last_login_at' => now(),
        ]);
        $candidate->notify('account', 'Welcome to TalentBridge', 'Your account is ready. Add your projects and experience so recruiters can discover what you can do.', '/experience');

        return response()->json($this->session($candidate), 201);
    }

    public function login(Request $request)
    {
        $data = $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        $candidate = Candidate::where('email', $data['email'])->first();
        if (! $candidate || ! $candidate->password || ! Hash::check($data['password'], $candidate->password)) {
            return response()->json(['message' => 'Email or password is incorrect.'], 422);
        }
        $candidate->forceFill(['last_login_at' => now()])->save();

        return response()->json($this->session($candidate));
    }

    /** Who is signed in (validates a session token). */
    public function me(string $token)
    {
        $candidate = Candidate::where('portal_token', $token)->firstOrFail();

        return response()->json($this->session($candidate));
    }

    /** Set a first password, or change it (current password required once one exists). */
    public function setPassword(Request $request, string $token)
    {
        $candidate = Candidate::where('portal_token', $token)->firstOrFail();
        $data = $request->validate([
            'current_password' => $candidate->password ? 'required|string' : 'nullable',
            'password' => ['required', 'confirmed', Password::min(8)],
        ]);
        if ($candidate->password && ! Hash::check($data['current_password'], $candidate->password)) {
            return response()->json(['message' => 'Your current password is incorrect.'], 422);
        }
        $first = ! $candidate->password;
        $candidate->forceFill(['password' => $data['password']])->save();

        return response()->json([
            'message' => $first ? 'Password set. You can now sign in with your email.' : 'Password changed.',
        ] + $this->session($candidate));
    }
}
