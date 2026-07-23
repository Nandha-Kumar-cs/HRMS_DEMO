<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\User;
use App\Services\SSOService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class SSOController extends Controller
{
    protected SSOService $sso;

    public function __construct(SSOService $sso)
    {
        $this->sso = $sso;
    }

    // ── Step 1: Redirect to SSO login ────────────────────────────────────────

    /**
     * Kick off SSO login — redirect the browser to the SSO authorization page.
     */
    public function redirect(Request $request)
    {
        if (!config('magdyn.sso.enabled')) {
            return redirect()->route('login')->with('error', 'SSO login is not enabled.');
        }

        // Store the page the user was trying to reach so we can send them there
        // after a successful login.
        $returnTo = $request->get('return_to') ?? url()->previous(route('dashboard'));

        return $this->sso->login($returnTo);
    }

    // ── Step 2: Handle SSO callback ──────────────────────────────────────────

    /**
     * Handle the return from the SSO server.
     * Exchanges the auth code, provisions the local user, and logs them in.
     */
    public function callback(Request $request)
    {
        if (!config('magdyn.sso.enabled')) {
            return redirect()->route('login')->with('error', 'SSO login is not enabled.');
        }

        // SSO server reported an error
        if ($request->has('error')) {
            $msg = $request->get('error_description', $request->get('error', 'Unknown SSO error'));
            return redirect()->route('login')->with('error', 'SSO error: ' . $msg);
        }

        // Exchange the authorization code for user data
        try {
            $userInfo = $this->sso->handleCallback(
                $request->get('code', ''),
                $request->get('state', '')
            );
        } catch (\Throwable $e) {
            return redirect()->route('login')
                ->with('error', 'SSO login failed: ' . $e->getMessage());
        }

        // ── Provision / sync local user record ───────────────────────────────
        //
        // The SSO is the source of truth for identity.
        // We create a local User if one doesn't exist yet, then keep name &
        // role in sync on every login so changes made in the SSO admin panel
        // take effect automatically.

        $displayName = $userInfo['full_name'] ?? $userInfo['username'] ?? $userInfo['email'];
        $role        = $this->mapSSORole($userInfo['roles'] ?? []);

        $user = User::firstOrCreate(
            ['email' => $userInfo['email']],
            [
                'name'     => $displayName,
                'password' => bcrypt(Str::random(32)), // unusable password — auth is SSO only
                'role'     => $role,
            ]
        );

        // Sync name / role in case they changed on the SSO side
        $updates = [];
        if ($user->name !== $displayName)  $updates['name'] = $displayName;
        if ($user->role !== $role)         $updates['role'] = $role;
        if ($updates) $user->update($updates);

        // Log in via Laravel Auth
        Auth::login($user, remember: true);

        ActivityLog::record(
            'login',
            'Auth',
            'SSO login: ' . $user->name . ' (' . $user->email . ')',
            $user->id,
            $user->name
        );

        return redirect()->to($this->sso->returnTo());
    }

    // ── Logout ───────────────────────────────────────────────────────────────

    /**
     * Log out of Laravel and trigger a global SSO logout
     * (signs the user out of all connected apps).
     */
    public function logout(Request $request)
    {
        $user = Auth::user();

        if ($user) {
            ActivityLog::record(
                'logout',
                'Auth',
                'SSO logout: ' . $user->name . ' (' . $user->email . ')',
                $user->id,
                $user->name
            );
        }

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        if (config('magdyn.sso.enabled')) {
            return $this->sso->logout(route('login'));
        }

        return redirect()->route('login');
    }

    // ── Helpers ──────────────────────────────────────────────────────────────

    /**
     * Map SSO role names to this app's role strings.
     *
     * SSO roles → HRMS roles:
     *   admin    → admin    (full access)
     *   manager  → manager  (team & payroll)
     *   anything else → staff (view own records)
     *
     * Extend this mapping if the SSO admin assigns custom role names.
     */
    protected function mapSSORole(array $roles): string
    {
        if (in_array('admin',   $roles, true)) return 'admin';
        if (in_array('manager', $roles, true)) return 'manager';

        return 'staff';
    }

    // ── JWT verification (kept for API / webhook use) ────────────────────────

    /**
     * Verify a JWT issued by the SSO provider (HS256 shared-secret).
     * Returns the decoded payload, or null if invalid / expired.
     */
    public static function verifyJwt(string $token): ?array
    {
        $secret = config('magdyn.sso.jwt_secret');
        if (!$secret) return null;

        $parts = explode('.', $token);
        if (count($parts) !== 3) return null;

        [$header, $payload, $signature] = $parts;

        $expected = rtrim(strtr(base64_encode(
            hash_hmac('sha256', "$header.$payload", $secret, true)
        ), '+/', '-_'), '=');

        if (!hash_equals($expected, $signature)) return null;

        $decoded = json_decode(base64_decode(strtr($payload, '-_', '+/')), true);
        if (!$decoded) return null;

        if (isset($decoded['exp']) && $decoded['exp'] < time()) return null;

        return $decoded;
    }
}
