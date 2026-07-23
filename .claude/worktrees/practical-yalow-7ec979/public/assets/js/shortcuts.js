/**
 * MagDyn Keyboard Shortcut System
 * Global shortcuts (Alt+key) + per-page local shortcuts via window.pageShortcuts
 * Alt held → body.alt-active → .sc chars get underlined
 */
(function () {
    'use strict';

    // ── Global shortcut map ──────────────────────────────────────
    var GLOBAL_SHORTCUTS = {
        'd': { label: 'Dashboard',   action: function () { go('/') } },
        'e': { label: 'Employees',   action: function () { go('/employees') } },
        'p': { label: 'Payroll',     action: function () { go('/salary-slips') } },
        'a': { label: 'Attendance',  action: function () { go('/attendance') } },
        'r': { label: 'Reports',     action: function () { go('/reports/monthly-benefits') } },
        's': { label: 'Settings',    action: function () { go('/salary-components') } },
        't': { label: 'Training',    action: function () { go('/training') } },
        'n': { label: 'New (page)',  action: function () { clickNew() } },
        '/': { label: 'Search',      action: function () { focusSearch() } },
        '?': { label: 'Shortcuts',   action: function () { toggleHelp() } },
    };

    // ── Helpers ──────────────────────────────────────────────────
    function go(path) {
        var base = (window.appBase || '').replace(/\/$/, '');
        window.location.href = base + path;
    }

    function clickNew() {
        var btn = document.querySelector('[data-shortcut="new"], .btn-new, a[href*="create"]');
        if (btn) btn.click();
    }

    function focusSearch() {
        var el = document.querySelector('.dataTables_filter input, input[type="search"], input[name="search"]');
        if (el) { el.focus(); el.select(); }
    }

    // ── Alt-key press detection ──────────────────────────────────
    var altDown = false;

    document.addEventListener('keydown', function (e) {
        // Only Alt, no Ctrl/Meta (avoids browser shortcuts)
        if (e.key === 'Alt' && !e.ctrlKey && !e.metaKey) {
            altDown = true;
            document.body.classList.add('alt-active');
            return;
        }

        if (!altDown) return;
        if (e.ctrlKey || e.metaKey) return;

        // Ignore when typing in form fields
        var tag = document.activeElement ? document.activeElement.tagName : '';
        if (tag === 'INPUT' || tag === 'TEXTAREA' || tag === 'SELECT') {
            if (e.key !== 'Escape') return;
        }

        var key = e.key.toLowerCase();

        // Local page shortcuts take priority over global
        var localShortcuts = (typeof window.pageShortcuts === 'object') ? window.pageShortcuts : {};
        if (localShortcuts[key]) {
            e.preventDefault();
            localShortcuts[key].action();
            return;
        }

        // Global shortcuts
        if (GLOBAL_SHORTCUTS[key]) {
            e.preventDefault();
            GLOBAL_SHORTCUTS[key].action();
        }
    });

    document.addEventListener('keyup', function (e) {
        if (e.key === 'Alt') {
            altDown = false;
            document.body.classList.remove('alt-active');
        }
    });

    // Reset alt state if window loses focus
    window.addEventListener('blur', function () {
        altDown = false;
        document.body.classList.remove('alt-active');
    });

    // ── Shortcut Help Overlay ────────────────────────────────────
    function toggleHelp() {
        var el = document.getElementById('shortcut-help');
        if (!el) { buildHelp(); el = document.getElementById('shortcut-help'); }
        el.classList.toggle('visible');
    }

    function buildHelp() {
        var el = document.createElement('div');
        el.id = 'shortcut-help';

        var globalRows = Object.keys(GLOBAL_SHORTCUTS).map(function (k) {
            return '<tr><td>Alt + ' + k.toUpperCase() + '</td><td>' + GLOBAL_SHORTCUTS[k].label + '</td></tr>';
        }).join('');

        var localShortcuts = (typeof window.pageShortcuts === 'object') ? window.pageShortcuts : {};
        var localRows = Object.keys(localShortcuts).map(function (k) {
            return '<tr><td>Alt + ' + k.toUpperCase() + '</td><td>' + (localShortcuts[k].label || k) + '</td></tr>';
        }).join('');

        el.innerHTML =
            '<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:10px;">' +
            '<h6 style="margin:0">⌨ Keyboard Shortcuts</h6>' +
            '<button onclick="document.getElementById(\'shortcut-help\').classList.remove(\'visible\')" ' +
            'style="background:none;border:none;color:#94a3b8;cursor:pointer;font-size:16px;padding:0">×</button>' +
            '</div>' +
            (localRows ? '<div style="font-size:10px;text-transform:uppercase;letter-spacing:1px;color:#64748b;margin-bottom:6px">Page Shortcuts</div>' +
                '<table>' + localRows + '</table><hr style="border-color:#1e293b;margin:10px 0">' : '') +
            '<div style="font-size:10px;text-transform:uppercase;letter-spacing:1px;color:#64748b;margin-bottom:6px">Global Shortcuts</div>' +
            '<table>' + globalRows + '</table>';

        document.body.appendChild(el);

        // Click outside to close
        document.addEventListener('click', function handler(ev) {
            if (!el.contains(ev.target)) {
                el.classList.remove('visible');
                document.removeEventListener('click', handler);
            }
        });
    }

    // ── Toast notification API ───────────────────────────────────
    window.mdToast = function (message, type, duration) {
        type = type || 'info';
        duration = duration || 3500;

        var container = document.getElementById('md-toast-container');
        if (!container) {
            container = document.createElement('div');
            container.id = 'md-toast-container';
            document.body.appendChild(container);
        }

        var icons = { success: '✓', error: '✕', info: 'ℹ' };
        var toast = document.createElement('div');
        toast.className = 'md-toast md-toast-' + type;
        toast.innerHTML =
            '<span class="md-toast-icon">' + (icons[type] || icons.info) + '</span>' +
            '<span>' + message + '</span>';

        container.appendChild(toast);

        setTimeout(function () {
            toast.classList.add('fade-out');
            setTimeout(function () { toast.remove(); }, 350);
        }, duration);
    };

    // ── PWA Install prompt ───────────────────────────────────────
    var deferredPrompt = null;
    window.addEventListener('beforeinstallprompt', function (e) {
        e.preventDefault();
        deferredPrompt = e;
        var banner = document.getElementById('pwa-install-banner');
        if (banner) banner.classList.add('visible');
    });

    window.mdInstallPWA = function () {
        if (!deferredPrompt) return;
        deferredPrompt.prompt();
        deferredPrompt.userChoice.then(function () { deferredPrompt = null; });
        var banner = document.getElementById('pwa-install-banner');
        if (banner) banner.classList.remove('visible');
    };

    // ── Service Worker registration ──────────────────────────────
    if ('serviceWorker' in navigator) {
        window.addEventListener('load', function () {
            var swUrl = (window.appBase || '') + '/sw.js';
            navigator.serviceWorker.register(swUrl).then(function (reg) {
                // Ready for push notifications
                window._swReg = reg;
            }).catch(function (err) {
                console.warn('SW registration failed:', err);
            });
        });
    }

    // ── Push notification subscription ──────────────────────────
    window.mdRequestPushPermission = function () {
        if (!('Notification' in window)) return;
        Notification.requestPermission().then(function (perm) {
            if (perm === 'granted' && window._swReg) {
                subscribeUserToPush(window._swReg);
            }
        });
    };

    function subscribeUserToPush(reg) {
        var vapidKey = window.vapidPublicKey || '';
        if (!vapidKey) return;
        var applicationServerKey = urlBase64ToUint8Array(vapidKey);
        reg.pushManager.subscribe({
            userVisibleOnly: true,
            applicationServerKey: applicationServerKey
        }).then(function (sub) {
            var token = document.querySelector('meta[name="csrf-token"]');
            fetch('/push/subscribe', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': token ? token.content : ''
                },
                body: JSON.stringify(sub)
            });
        });
    }

    function urlBase64ToUint8Array(base64String) {
        var padding = '='.repeat((4 - base64String.length % 4) % 4);
        var base64 = (base64String + padding).replace(/-/g, '+').replace(/_/g, '/');
        var rawData = window.atob(base64);
        var outputArray = new Uint8Array(rawData.length);
        for (var i = 0; i < rawData.length; ++i) {
            outputArray[i] = rawData.charCodeAt(i);
        }
        return outputArray;
    }

})();
