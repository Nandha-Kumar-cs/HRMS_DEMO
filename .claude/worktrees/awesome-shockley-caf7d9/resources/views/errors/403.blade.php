@extends('layouts.app')
@section('title', '403 — Access Denied')

@section('content')
<div class="d-flex flex-column align-items-center justify-content-center py-5 text-center">
    <div style="font-size:6rem;color:#e74a3b"><i class="fa fa-lock"></i></div>
    <h1 class="fw-bold mt-3">403</h1>
    <h4 class="text-muted">Access Denied</h4>
    <p class="text-muted">You do not have permission to access this page.<br>Please contact your administrator if you believe this is an error.</p>
    <a href="{{ route('dashboard') }}" class="btn btn-primary mt-3"><i class="fa fa-house me-2"></i>Back to Dashboard</a>
</div>
@endsection
