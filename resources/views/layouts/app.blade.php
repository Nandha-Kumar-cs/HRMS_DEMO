<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#0f172a">
    <meta name="app-base" content="{{ rtrim(config('app.url'), '/') }}">
    @if(config('magdyn.pwa.vapid_public_key'))
    <meta name="vapid-public-key" content="{{ config('magdyn.pwa.vapid_public_key') }}">
    @endif
    <title>@yield('title', 'HRMS') — {{ config('magdyn.app_name', 'HRMS') }}</title>

    @php
        $faviconUrl = \Illuminate\Support\Facades\Cache::remember('app_favicon_url', 3600, function () {
            $entity = \App\Models\Entity::where('name', 'like', '%magneto%')
                ->orWhere('name', 'like', '%dynamics%')
                ->first();
            if ($entity && $entity->logo) {
                $path = public_path('storage/entities/' . $entity->logo);
                if (file_exists($path)) {
                    return asset('storage/entities/' . $entity->logo);
                }
            }
            return null;
        });
    @endphp
    @if($faviconUrl)
        <link rel="icon" type="image/png" href="{{ $faviconUrl }}">
        <link rel="shortcut icon" href="{{ $faviconUrl }}">
    @else
        <link rel="icon" href="{{ asset('favicon.ico') }}">
    @endif

    <!-- PWA Manifest -->
    <link rel="manifest" href="{{ asset('manifest.json') }}">
    <link rel="apple-touch-icon" href="{{ $faviconUrl ?? asset('assets/img/icon-192.png') }}">

    <!-- Bootstrap 5 -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <!-- DataTables -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.5.0/css/responsive.bootstrap5.min.css">
    <!-- SweetAlert2 -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">

    <!-- MagDyn Design System -->
    <link rel="stylesheet" href="{{ asset('assets/css/magdyn-base.css') }}">

    @stack('styles')
</head>
<body>

@include('partials.sidebar')

<div id="main-content">
    @include('partials.navbar')

    <main>
        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show mx-1 mb-0" role="alert">
                <i class="fa fa-check-circle me-2"></i>{{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif
        @if(session('error'))
            <div class="alert alert-danger alert-dismissible fade show mx-1 mb-0" role="alert">
                <i class="fa fa-exclamation-circle me-2"></i>{{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        @yield('content')
    </main>

    @include('partials.footer')
</div>

<!-- PWA install banner -->
<div id="pwa-install-banner">
    <span><i class="fa fa-mobile-screen me-2"></i>Install HRMS as an app for faster access</span>
    <div class="d-flex gap-2">
        <button onclick="window.mdInstallPWA()" class="btn btn-sm btn-light fw-600">Install</button>
        <button onclick="this.closest('#pwa-install-banner').classList.remove('visible')"
                class="btn btn-sm" style="color:rgba(255,255,255,.7)">Dismiss</button>
    </div>
</div>

<!-- Toast container (JS-driven) -->
<div id="md-toast-container"></div>

<!-- jQuery -->
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<!-- Bootstrap 5 JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<!-- DataTables -->
<script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdn.datatables.net/responsive/2.5.0/js/dataTables.responsive.min.js"></script>
<script src="https://cdn.datatables.net/responsive/2.5.0/js/responsive.bootstrap5.min.js"></script>
<!-- SweetAlert2 -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>

<script>
    // Expose app base URL for shortcuts.js
    window.appBase = document.querySelector('meta[name="app-base"]')?.content || '';
    window.vapidPublicKey = document.querySelector('meta[name="vapid-public-key"]')?.content || '';

    // CSRF for AJAX
    $.ajaxSetup({ headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') } });

    // ── Sidebar Collapse (persisted via localStorage) ────────────────
    (function () {
        var STORAGE_KEY = 'hrms_sidebar_collapsed';
        var $sidebar    = $('#sidebar');
        var $main       = $('#main-content');
        var $icon       = $('#sidebarCollapseIcon');

        function applyCollapsed(collapsed, animate) {
            if (!animate) $sidebar.css('transition', 'none');
            $sidebar.toggleClass('sidebar-collapsed', collapsed);
            $main.toggleClass('sidebar-collapsed-content', collapsed);
            $icon.toggleClass('fa-angles-left', !collapsed)
                 .toggleClass('fa-angles-right', collapsed);
            if (!animate) { $sidebar[0].offsetHeight; $sidebar.css('transition', ''); }
        }

        // Restore saved state immediately (no animation)
        var saved = localStorage.getItem(STORAGE_KEY) === '1';
        applyCollapsed(saved, false);

        $('#sidebarCollapseBtn').on('click', function () {
            var nowCollapsed = !$sidebar.hasClass('sidebar-collapsed');
            applyCollapsed(nowCollapsed, true);
            localStorage.setItem(STORAGE_KEY, nowCollapsed ? '1' : '0');
        });
    })();

    // Mobile sidebar toggle
    $('#sidebarToggle').on('click', function () {
        $('#sidebar').toggleClass('md-open');
    });

    // Sidebar accordion
    document.addEventListener('show.bs.collapse', function (e) {
        if (!e.target.closest('#sidebar')) return;
        document.querySelectorAll('#sidebar .collapse.show').forEach(function (open) {
            if (open.id !== e.target.id) {
                var inst = bootstrap.Collapse.getInstance(open);
                if (inst) inst.hide();
            }
        });
    });

    // Global delete handler (data-url buttons)
    $(document).on('click', '.btn-delete', function () {
        var url = $(this).data('url');
        Swal.fire({
            title: 'Delete this record?',
            text: 'This action cannot be undone.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc2626',
            cancelButtonColor: '#64748b',
            confirmButtonText: 'Yes, delete',
        }).then(function (result) {
            if (result.isConfirmed) {
                $.ajax({
                    url: url, type: 'DELETE',
                    success: function (res) {
                        window.mdToast(res.message || 'Deleted.', 'success');
                        if (window._dataTable) window._dataTable.ajax.reload(null, false);
                        else location.reload();
                    },
                    error: function () { window.mdToast('Delete failed.', 'error'); }
                });
            }
        });
    });
</script>

<!-- MagDyn Shortcuts (must be after jQuery) -->
<script src="{{ asset('assets/js/shortcuts.js') }}"></script>

@stack('scripts')
</body>
</html>
