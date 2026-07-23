<?php

namespace App\Services;

use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Str;
use Exception;

/**
 * Laravel-native MagDyn SSO Service.
 *
 * Mirrors the SSOClient.php protocol exactly (based on the library source):
 *
 *   Authorize : GET  {base}/authorize.php?client_id=&redirect_uri=&state=
 *   Token     : POST {base}/api/token.php          → {access_token, expires_in, user}
 *   Userinfo  : GET  {base}/api/userinfo.php        (Authorization: Bearer …)
 *   Logout    : GET  {base}/logout.php?redirect=…
 *   Sync      : POST {base}/api/register_permissions.php
 *
 * Configuration: config/magdyn.php (sso.*) / .env (MAGDYN_SSO_*)
 */
class SSOService
{
    protected array $config;

    public function __construct()
    {
        $this->config = config('magdyn.sso', []);
    }

    // ── Helpers ──────────────────────────────────────────────────────────────

    protected function endpoint(string $name): string
    {
        $base  = rtrim($this->config['provider_url'] ?? '', '/');
        $paths = $this->config['endpoints'] ?? [];
        $path  = $paths[$name] ?? "/$name";

        return $base . $path;
    }

    // ── Auth flow ────────────────────────────────────────────────────────────

    /**
     * Step 1: Redirect to SSO authorization page.
     * Matches SSOClient::login() — uses /authorize.php
     */
    public function login(?string $returnTo = null): RedirectResponse
    {
        $state = bin2hex(random_bytes(16));  // matches SSOClient exactly

        Session::put('sso_state',     $state);
        Session::put('sso_return_to', $returnTo ?? route('dashboard'));

        $params = http_build_query([
            'client_id'    => $this->config['client_id'],
            'redirect_uri' => $this->config['redirect_uri'],
            'state'        => $state,
        ]);

        return redirect($this->endpoint('authorize') . '?' . $params);
    }

    /**
     * Step 2: Exchange auth code for token + user.
     *
     * The token endpoint returns:
     *   { access_token, expires_in, user: { id, email, username, full_name, roles, permissions } }
     *
     * User data is embedded — no separate userinfo call needed.
     *
     * @throws Exception
     * @return array  The user payload.
     */
    public function handleCallback(string $code, string $state): array
    {
        // CSRF state check
        if (!Session::has('sso_state') || !hash_equals((string) Session::get('sso_state'), (string) $state)) {
            throw new Exception('Invalid state — possible CSRF.');
        }
        Session::forget('sso_state');

        // Exchange code → token (server-to-server, matches SSOClient::handle_callback)
        $response = Http::asForm()->post($this->endpoint('token'), [
            'code'          => $code,
            'client_id'     => $this->config['client_id'],
            'client_secret' => $this->config['client_secret'],
            'redirect_uri'  => $this->config['redirect_uri'],
        ]);

        if ($response->failed()) {
            throw new Exception('Token exchange failed: ' . $response->body());
        }

        $data = $response->json();

        if (empty($data['access_token'])) {
            throw new Exception('Token exchange failed: ' . $response->body());
        }

        if (empty($data['user'])) {
            throw new Exception('No user data in token response.');
        }

        $user = $data['user'];

        // Persist in Laravel session
        Session::put('sso_user',         $user);
        Session::put('sso_access_token', $data['access_token']);

        return $user;
    }

    /**
     * Redirect to SSO global logout.
     * Matches SSOClient::logout() — uses /logout.php?redirect=…
     */
    public function logout(?string $returnTo = null): RedirectResponse
    {
        Session::forget(['sso_user', 'sso_access_token', 'sso_state', 'sso_return_to']);

        $url = $this->endpoint('logout');
        if ($returnTo) {
            $url .= '?' . http_build_query(['redirect' => $returnTo]);
        }

        return redirect($url);
    }

    // ── Session accessors ────────────────────────────────────────────────────

    public function user(): ?array
    {
        return Session::get('sso_user');
    }

    public function isLoggedIn(): bool
    {
        return Session::has('sso_user');
    }

    /** Consumes the stored return-to URL (falls back to dashboard). */
    public function returnTo(): string
    {
        return Session::pull('sso_return_to', route('dashboard'));
    }

    // ── Roles & Permissions ──────────────────────────────────────────────────

    public function roles(): array
    {
        return $this->user()['roles'] ?? [];
    }

    public function permissions(): array
    {
        return $this->user()['permissions'] ?? [];
    }

    /** ANY-of check (pass string or array). */
    public function hasRole(string|array $role): bool
    {
        $roles = $this->roles();
        $check = is_array($role) ? $role : [$role];

        return count(array_intersect($check, $roles)) > 0;
    }

    /** Permission ANY-of check. Admins always pass. */
    public function can(string|array $permission): bool
    {
        if ($this->hasRole('admin')) return true;

        $perms = $this->permissions();
        $check = is_array($permission) ? $permission : [$permission];

        return count(array_intersect($check, $perms)) > 0;
    }

    /** ALL-of permission check. */
    public function canAll(array $permissions): bool
    {
        if ($this->hasRole('admin')) return true;

        return count(array_diff($permissions, $this->permissions())) === 0;
    }

    // ── Refresh ──────────────────────────────────────────────────────────────

    /**
     * Re-fetch user info from the SSO server.
     * Matches SSOClient::refresh_user() — uses /api/userinfo.php
     */
    public function refreshUser(): ?array
    {
        $token = Session::get('sso_access_token');
        if (!$token) return null;

        $response = Http::withToken($token)->get($this->endpoint('userinfo'));

        if ($response->ok()) {
            $u = $response->json();
            if ($u) Session::put('sso_user', $u);
        }

        return Session::get('sso_user');
    }

    // ── Permissions manifest sync ─────────────────────────────────────────────

    /**
     * Publish this app's permissions manifest to the SSO server.
     * Endpoint: POST /api/register_permissions.php
     *
     * @throws Exception on HTTP failure.
     */
    public function registerPermissions(array $manifest): array
    {
        // Try JSON body first (some SSO server versions prefer it)
        $response = Http::acceptJson()->post($this->endpoint('sync'), [
            'client_id'     => $this->config['client_id'],
            'client_secret' => $this->config['client_secret'],
            'permissions'   => $manifest['permissions'] ?? $manifest,
            'roles'         => $manifest['roles']       ?? [],
        ]);

        if ($response->failed()) {
            throw new Exception('Permissions sync failed: ' . $response->body());
        }

        return $response->json() ?? [];
    }
}
