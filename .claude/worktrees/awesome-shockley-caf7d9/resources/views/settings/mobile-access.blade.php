@extends('layouts.app')
@section('title','Mobile Access Settings')
@section('breadcrumb')
<li class="breadcrumb-item active">Mobile Access</li>
@endsection
@section('content')
<div class="card page-card">
    <div class="card-header bg-white py-3 d-flex align-items-center gap-2">
        <i class="fa fa-mobile-screen text-primary fs-5"></i>
        <h5 class="mb-0 fw-semibold">Mobile Access / PWA Module Settings</h5>
    </div>
    <div class="card-body">
        <p class="text-muted mb-4" style="font-size:13.5px">
            Choose which modules are accessible when HRMS is used as a Progressive Web App on mobile devices.
            Modules that are disabled here will not appear in the mobile navigation.
        </p>
        <form action="{{ route('mobile-access.update') }}" method="POST">
            @csrf @method('PUT')
            <div class="row g-3">
                @foreach($modules as $key => $label)
                @php $enabled = isset($settings[$key]) ? (bool) $settings[$key] : true; @endphp
                <div class="col-md-4">
                    <div class="d-flex align-items-center justify-content-between p-3"
                         style="background:var(--md-surface-2);border:1px solid var(--md-border);border-radius:var(--md-radius)">
                        <div>
                            <div class="fw-600" style="font-size:13.5px">{{ $label }}</div>
                            <div style="font-size:11px;color:var(--md-text-muted)">{{ $key }}</div>
                        </div>
                        <div class="form-check form-switch mb-0">
                            <input class="form-check-input" type="checkbox"
                                   name="modules[{{ $key }}]" id="mod_{{ $key }}"
                                   role="switch" {{ $enabled ? 'checked' : '' }}>
                        </div>
                    </div>
                </div>
                @endforeach
            </div>
            <div class="mt-4 d-flex gap-2">
                <button type="submit" class="btn btn-primary">
                    <i class="fa fa-save me-1"></i>Save Settings
                </button>
            </div>
        </form>

        <hr class="my-4">

        <h6 class="fw-semibold mb-3" style="font-size:13px">PWA Status</h6>
        <div class="row g-3">
            <div class="col-md-3">
                <div class="p-3 text-center" style="background:var(--md-surface-2);border:1px solid var(--md-border);border-radius:var(--md-radius)">
                    <div id="sw-status" style="font-size:22px">⏳</div>
                    <div class="fw-600 mt-1" style="font-size:13px">Service Worker</div>
                    <div id="sw-status-text" style="font-size:11px;color:var(--md-text-muted)">Checking...</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="p-3 text-center" style="background:var(--md-surface-2);border:1px solid var(--md-border);border-radius:var(--md-radius)">
                    <div id="notif-status" style="font-size:22px">🔔</div>
                    <div class="fw-600 mt-1" style="font-size:13px">Push Notifications</div>
                    <div id="notif-status-text" style="font-size:11px;color:var(--md-text-muted)">Checking...</div>
                    <button onclick="window.mdRequestPushPermission()" class="btn btn-xs btn-outline-primary mt-2">
                        Enable
                    </button>
                </div>
            </div>
            <div class="col-md-3">
                <div class="p-3 text-center" style="background:var(--md-surface-2);border:1px solid var(--md-border);border-radius:var(--md-radius)">
                    <div id="pwa-install-status" style="font-size:22px">📲</div>
                    <div class="fw-600 mt-1" style="font-size:13px">Install App</div>
                    <div style="font-size:11px;color:var(--md-text-muted)">Add to home screen</div>
                    <button onclick="window.mdInstallPWA()" class="btn btn-xs btn-outline-primary mt-2">
                        Install
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
@push('scripts')
<script>
// Check service worker status
if ('serviceWorker' in navigator) {
    navigator.serviceWorker.getRegistration().then(function(reg) {
        if (reg) {
            document.getElementById('sw-status').textContent = '✅';
            document.getElementById('sw-status-text').textContent = 'Active';
        } else {
            document.getElementById('sw-status').textContent = '❌';
            document.getElementById('sw-status-text').textContent = 'Not registered';
        }
    });
} else {
    document.getElementById('sw-status').textContent = '❌';
    document.getElementById('sw-status-text').textContent = 'Not supported';
}

// Check notification permission
if ('Notification' in window) {
    var perm = Notification.permission;
    document.getElementById('notif-status').textContent = perm === 'granted' ? '✅' : (perm === 'denied' ? '🚫' : '🔔');
    document.getElementById('notif-status-text').textContent = perm === 'granted' ? 'Enabled' : (perm === 'denied' ? 'Blocked' : 'Not enabled');
}
</script>
@endpush
