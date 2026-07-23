<?php

/**
 * MagDyn SSO — Publish HRMS Permissions Manifest
 * ─────────────────────────────────────────────────────────────────────────────
 * Run from the project root via CLI:
 *   php public/sync_permissions.php
 *
 * Or via Artisan (recommended):
 *   php artisan sso:sync-permissions
 *
 * Or via browser (protected — only works when SSO is configured):
 *   http://localhost/Employee_Management/public/sync_permissions.php
 *
 * Re-run every time you add a new permission to app/Sso/manifest.php.
 *
 * PRODUCTION: restrict web access to this file in your web server config or
 * remove it and use the Artisan command only.
 * ─────────────────────────────────────────────────────────────────────────────
 */

// ── Bootstrap Laravel ────────────────────────────────────────────────────────
define('LARAVEL_START', microtime(true));

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

// ── Guard: SSO must be enabled and configured ────────────────────────────────
if (!config('magdyn.sso.enabled')) {
    abort_unless(PHP_SAPI === 'cli', 403, 'SSO is not enabled (MAGDYN_SSO_ENABLED=false).');
    fwrite(STDERR, "Error: SSO is not enabled. Set MAGDYN_SSO_ENABLED=true in .env first.\n");
    exit(1);
}

if (!config('magdyn.sso.client_id') || !config('magdyn.sso.client_secret')) {
    abort_unless(PHP_SAPI === 'cli', 403, 'SSO client credentials are not configured.');
    fwrite(STDERR, "Error: Set MAGDYN_SSO_CLIENT_ID and MAGDYN_SSO_CLIENT_SECRET in .env first.\n");
    exit(1);
}

// ── Run sync ─────────────────────────────────────────────────────────────────
use App\Services\SSOService;

$sso      = new SSOService();
$manifest = require __DIR__ . '/../app/Sso/manifest.php';

try {
    $result = $sso->registerPermissions($manifest);
} catch (Throwable $e) {
    if (PHP_SAPI === 'cli') {
        fwrite(STDERR, "Sync failed: " . $e->getMessage() . "\n");
        exit(1);
    }
    http_response_code(500);
    echo '<pre style="color:red">Sync failed: ' . htmlspecialchars($e->getMessage()) . '</pre>';
    exit;
}

// ── Output result ─────────────────────────────────────────────────────────────
if (PHP_SAPI === 'cli') {
    echo "Sync OK\n";
    if (!empty($result['counts'])) {
        foreach ($result['counts'] as $k => $v) {
            echo "  $k: $v\n";
        }
    } else {
        echo json_encode($result, JSON_PRETTY_PRINT) . "\n";
    }
} else {
    header('Content-Type: application/json');
    echo json_encode(['status' => 'ok', 'result' => $result], JSON_PRETTY_PRINT);
}
