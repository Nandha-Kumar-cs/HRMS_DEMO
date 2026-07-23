<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#0f172a">
    <title>Login — {{ config('magdyn.app_name', 'HRMS') }}</title>
    <link rel="manifest" href="{{ asset('manifest.json') }}">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="{{ asset('assets/css/magdyn-base.css') }}">
    <style>
        body {
            background: linear-gradient(135deg, #0f172a 0%, #1e293b 60%, #1e3a8a 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: var(--md-font);
        }
        .auth-card {
            background: #fff;
            border-radius: 14px;
            box-shadow: 0 25px 50px rgba(0,0,0,.35);
            overflow: hidden;
            width: 420px;
            max-width: 96vw;
        }
        .auth-header {
            background: var(--md-sidebar-bg);
            padding: 28px 32px 24px;
            text-align: center;
        }
        .auth-logo {
            width: 56px; height: 56px;
            background: var(--md-primary);
            border-radius: 12px;
            display: flex; align-items: center; justify-content: center;
            margin: 0 auto 14px;
            font-size: 24px; color: #fff;
        }
        .auth-header h3 { color: #fff; font-size: 20px; font-weight: 700; margin: 0 0 4px; }
        .auth-header p  { color: #64748b; font-size: 12.5px; margin: 0; }
        .auth-body { padding: 28px 32px; }
        .auth-body .form-label {
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .6px;
            color: var(--md-text-secondary);
        }
        .btn-auth {
            background: var(--md-primary);
            border: none;
            color: #fff;
            font-weight: 600;
            font-size: 14px;
            padding: 10px;
            border-radius: var(--md-radius);
            width: 100%;
            transition: background var(--md-transition);
        }
        .btn-auth:hover { background: var(--md-primary-dark); color: #fff; }
        .btn-sso {
            background: #fff;
            border: 1.5px solid var(--md-border);
            color: var(--md-text);
            font-weight: 500;
            font-size: 13.5px;
            padding: 9px;
            border-radius: var(--md-radius);
            width: 100%;
            transition: all var(--md-transition);
            display: flex; align-items: center; justify-content: center; gap: 8px;
        }
        .btn-sso:hover { border-color: var(--md-primary); color: var(--md-primary); background: var(--md-primary-pale); }
        .auth-divider {
            display: flex; align-items: center; gap: 12px;
            margin: 16px 0;
            color: var(--md-text-muted);
            font-size: 11px;
        }
        .auth-divider::before, .auth-divider::after {
            content: ''; flex: 1; border-top: 1px solid var(--md-border);
        }
    </style>
</head>
<body>
    @yield('content')
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
