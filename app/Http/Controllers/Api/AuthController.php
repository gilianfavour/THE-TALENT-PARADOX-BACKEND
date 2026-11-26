<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Candidate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

/**
 * Candidate authentication.
 *
 * Authentication is token-based: the candidate's portal_token doubles as
 * their session identity. A password is optional — candidates who applied
 * without one still get a portal link, but cannot use login/register.
 */
class AuthController extends Controller
{
    /**
     * GET /api/auth/me/{token}
     * Resolve a portal token to candidate identity. Used by the frontend
     * to validate a session cookie and by the adopt flow.
     */
    public function me(string $token)
    {
        $candidate = Candidate::where('portal_token', $token)->firstOrFail();

        return response()->json([
            'token' => $candidate->portal_token,
            'name' => $candidate->name,
            'email' => $candidate->email,
            'has_password' => ! empty($candidate->password),
        ]);
    }

    /**
     * POST /api/auth/login
     * Accepts { email, password } and returns the portal token.
     */
    public function login(Request $request)
    {
        $data = $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        $candidate = Candidate::where('email', $data['email'])->first();

        if (! $candidate || empty($candidate->password) || ! Hash::check($data['password'], $candidate->password)) {
            return response()->json(['message' => 'These credentials do not match our records.'], 401);
        }

        return response()->json([
            'token' => $candidate->portal_token,
            'name' => $candidate->name,
            'email' => $candidate->email,
            'has_password' => true,
        ]);
    }

    /**
     * POST /api/auth/register
     * Creates a new candidate account with a password.
     * If a candidate with the same email already exists and has no password,
     * it sets the password (adopt-on-register). If they already have a
     * password the request is rejected — they should log in instead.
     */
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
            if (! empty($existing->password)) {
                return response()->json([
                    'message' => 'An account with that email already exists. Please sign in.',
                ], 409);
            }

            // Candidate applied without a password — attach password to their existing profile.
            $existing->update([
                'name' => $data['name'],
                'phone' => $data['phone'] ?? $existing->phone,
                'location' => $data['location'] ?? $existing->location,
                'password' => Hash::make($data['password']),
            ]);

            return response()->json([
                'token' => $existing->portal_token,
                'name' => $existing->name,
                'email' => $existing->email,
                'has_password' => true,
            ], 200);
        }

        $candidate = Candidate::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'phone' => $data['phone'] ?? null,
            'location' => $data['location'] ?? null,
            'password' => Hash::make($data['password']),
            'portal_token' => \Illuminate\Support\Str::random(40),
            'status' => 'active',
        ]);

        return response()->json([
            'token' => $candidate->portal_token,
            'name' => $candidate->name,
            'email' => $candidate->email,
            'has_password' => true,
        ], 201);
    }

    /**
     * POST /api/portal/{token}/password
     * Set or change a password on an existing candidate account.
     */
    public function setPassword(Request $request, string $token)
    {
        $candidate = Candidate::where('portal_token', $token)->firstOrFail();

        $rules = [
            'password' => ['required', 'confirmed', Password::min(8)],
            'password_confirmation' => 'required|string',
        ];

        // If they already have a password, require the current one.
        if (! empty($candidate->password)) {
            $rules['current_password'] = 'required|string';
        }

        $data = $request->validate($rules);

        if (! empty($candidate->password) && ! Hash::check($data['current_password'], $candidate->password)) {
            return response()->json(['message' => 'Current password is incorrect.'], 422);
        }

        $candidate->update(['password' => Hash::make($data['password'])]);

        return response()->json([
            'message' => 'Password updated.',
            'token' => $candidate->portal_token,
        ]);
    }
}
