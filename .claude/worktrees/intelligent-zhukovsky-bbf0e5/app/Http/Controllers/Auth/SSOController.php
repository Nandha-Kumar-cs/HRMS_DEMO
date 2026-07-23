<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class SSOController extends Controller
{
    /**
     * Redirect the user to the SSO provider's authorization page.
     */
    public function redirect()
    {
        if (!config('magdyn.sso.enabled')) {
            return redirect()->route('login')->with('error', 'SSO is not enabled.');
        }

        $state = Str::random(40);
        session(['sso_state' => $state]);

        $params = http_build_query([
            'client_id'     => config('magdyn.sso.client_id'),
            'redirect_uri'  => config('magdyn.sso.redirect_uri'),
            'response_type' => 'code',
            'scope'         => 'openid profile email',
            'state'         => $state,
        ]);

        return redirect(config('magdyn.sso.provider_url') . '/oauth/authorize?' . $params);
    }

    /**
     * Handle the callback from the SSO provider.
     */
    public function callback(Request $request)
    {
        if (!config('magdyn.sso.enabled')) {
            return redirect()->route('login')->with('error', 'SSO is not enabled.');
        }

        // CSRF state check
        if ($request->state !== session('sso_state')) {
            return redirect()->route('login')->with('error', 'Invalid SSO state. Please try again.');
        }

        if ($request->has('error')) {
            return redirect()->route('login')
                ->with('error', 'SSO error: ' . $request->get('error_description', $request->get('error')));
        }

        // Exchange code for token
        $tokenResponse = Http::asForm()->post(
            config('magdyn.sso.provider_url') . '/oauth/token',
            [
                'grant_type'    => 'authorization_code',
                'client_id'     => config('magdyn.sso.client_id'),
                'client_secret' => config('magdyn.sso.client_secret'),
                'redirect_uri'  => config('magdyn.sso.redirect_uri'),
                'code'          => $request->code,
            ]
        );

        if ($tokenResponse->failed()) {
            return redirect()->route('login')->with('error', 'Failed to retrieve SSO token.');
        }

        $accessToken = $tokenResponse->json('access_token');

        // Fetch user info from provider
        $userInfo = Http::withToken($accessToken)
            ->get(config('magdyn.sso.provider_url') . '/api/user')
            ->json();

        if (empty($userInfo['email'])) {
            return redirect()->route('login')->with('error', 'Could not retrieve user info from SSO.');
        }

        // Find or provision the local user
        $user = User::firstOrCreate(
            ['email' => $userInfo['email']],
            [
                'name'     => $userInfo['name'] ?? $userInfo['email'],
                'password' => bcrypt(Str::random(32)),
                'role'     => $userInfo['role'] ?? 'staff',
            ]
        );

        // Update name in case it changed on SSO side
        if (isset($userInfo['name']) && $user->name !== $userInfo['name']) {
            $user->update(['name' => $userInfo['name']]);
        }

        Auth::login($user, true);
        session()->forget('sso_state');

        return redirect()->intended(route('dashboard'));
    }

    /**
     * Verify a JWT issued by the SSO provider (shared-secret HS256).
     * Returns the decoded payload or null on failure.
     */
    public static function verifyJwt(string $token): ?array
    {
        $secret = config('magdyn.sso.jwt_secret');
        if (!$secret) return null;

        $parts = explode('.', $token);
        if (count($parts) !== 3) return null;

        [$header, $payload, $signature] = $parts;

        $expectedSig = rtrim(strtr(base64_encode(
            hash_hmac('sha256', $header . '.' . $payload, $secret, true)
        ), '+/', '-_'), '=');

        if (!hash_equals($expectedSig, $signature)) return null;

        $decoded = json_decode(base64_decode(strtr($payload, '-_', '+/')), true);
        if (!$decoded) return null;

        // Check expiry
        if (isset($decoded['exp']) && $decoded['exp'] < time()) return null;

        return $decoded;
    }
}
