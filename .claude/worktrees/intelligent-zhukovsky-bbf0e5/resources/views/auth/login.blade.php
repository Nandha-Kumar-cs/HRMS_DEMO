@extends('layouts.auth')

@section('content')
<div class="auth-card">
    <div class="auth-header">
        <div class="auth-logo">&#9878;</div>
        <h3>{{ config('magdyn.app_name', 'HRMS') }}</h3>
        <p>{{ config('magdyn.company', '') }} · Human Resource Management</p>
    </div>
    <div class="auth-body">
        <h5 class="fw-semibold mb-1" style="font-size:16px">Sign in to your account</h5>
        <p class="mb-4" style="font-size:12.5px;color:var(--md-text-muted)">Enter your credentials to continue</p>

        @if($errors->any())
            <div class="alert alert-danger py-2 mb-3">
                <i class="fa fa-exclamation-circle me-2"></i>{{ $errors->first() }}
            </div>
        @endif
        @if(session('error'))
            <div class="alert alert-danger py-2 mb-3">
                <i class="fa fa-exclamation-circle me-2"></i>{{ session('error') }}
            </div>
        @endif
        @if(session('success'))
            <div class="alert alert-success py-2 mb-3">
                <i class="fa fa-check-circle me-2"></i>{{ session('success') }}
            </div>
        @endif

        <form action="{{ route('login.post') }}" method="POST">
            @csrf
            <div class="mb-3">
                <label class="form-label">Email Address</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="fa fa-envelope" style="font-size:12px"></i></span>
                    <input type="email" name="email"
                           class="form-control @error('email') is-invalid @enderror"
                           value="{{ old('email') }}" placeholder="you@company.com" required autofocus>
                </div>
            </div>
            <div class="mb-3">
                <label class="form-label">Password</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="fa fa-lock" style="font-size:12px"></i></span>
                    <input type="password" name="password" class="form-control"
                           placeholder="••••••••" required>
                </div>
            </div>
            <div class="mb-4 d-flex align-items-center justify-content-between">
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" name="remember" id="remember">
                    <label class="form-check-label" for="remember" style="font-size:12.5px">Remember me</label>
                </div>
            </div>
            <button type="submit" class="btn-auth mb-3">
                <i class="fa fa-right-to-bracket me-2"></i>Sign In
            </button>
        </form>

        @if(config('magdyn.sso.enabled'))
        <div class="auth-divider">or</div>
        <a href="{{ route('sso.redirect') }}" class="btn-sso">
            <i class="fa fa-shield-halved"></i>
            Sign in with {{ config('magdyn.company', 'MagDyn') }} SSO
        </a>
        @endif

        <p class="text-center mt-4 mb-0" style="font-size:11px;color:var(--md-text-muted)">
            v{{ config('magdyn.app_version', '2.0') }} &mdash; {{ config('magdyn.company', 'MagDyn') }}
        </p>
    </div>
</div>
@endsection
