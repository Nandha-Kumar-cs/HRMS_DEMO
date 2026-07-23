@extends('layouts.app')
@section('title','No Due Certificate')
@section('breadcrumb')
<li class="breadcrumb-item"><a href="{{ route('no-due.index') }}" class="text-decoration-none">No Due</a></li>
<li class="breadcrumb-item active">Certificate</li>
@endsection

@section('content')
<div class="card page-card mb-3">
    <div class="card-header bg-white py-3 d-flex align-items-center justify-content-between">
        <div class="d-flex align-items-center gap-2">
            <a href="{{ route('no-due.index') }}" class="btn btn-sm btn-outline-secondary"><i class="fa fa-arrow-left"></i></a>
            <h5 class="mb-0 fw-semibold">No Due Certificate — {{ $certificate->employee->full_name }}</h5>
        </div>
        <div class="d-flex gap-2">
            @if($certificate->status === 'pending' && $certificate->employee->currentAssets->isEmpty())
                <form action="{{ route('no-due.approve', $certificate) }}" method="POST" class="d-inline">
                    @csrf
                    <button type="submit" class="btn btn-sm btn-success"><i class="fa fa-check me-1"></i>Approve</button>
                </form>
            @endif
            <button onclick="window.print()" class="btn btn-sm btn-outline-secondary"><i class="fa fa-print me-1"></i>Print</button>
        </div>
    </div>
</div>

@php $pendingAssets = $certificate->employee->currentAssets; @endphp

@if($pendingAssets->isNotEmpty())
<div class="alert alert-danger">
    <i class="fa fa-exclamation-triangle me-2"></i>
    <strong>Cannot Approve!</strong> The following assets are still assigned to this employee and must be returned first:
    <ul class="mb-0 mt-2">
        @foreach($pendingAssets as $a)
            <li>{{ $a->asset->asset_name }} ({{ $a->asset->type_label }}) — Issued {{ $a->issue_date->format('d M Y') }}</li>
        @endforeach
    </ul>
</div>
@endif

<div class="card page-card" id="printArea">
    <div class="card-body p-5">
        <div class="text-center mb-4">
            <h3 class="fw-bold">No Due Certificate</h3>
            <p class="text-muted">Date: {{ $certificate->generated_date->format('d F Y') }}</p>
        </div>
        <hr>
        <div class="row mb-4">
            <div class="col-md-6">
                <table class="table table-sm table-borderless">
                    <tr><th class="text-muted">Employee Name</th><td><strong>{{ $certificate->employee->full_name }}</strong></td></tr>
                    <tr><th class="text-muted">Employee Code</th><td>{{ $certificate->employee->employee_code }}</td></tr>
                    <tr><th class="text-muted">Designation</th><td>{{ $certificate->employee->designation?->name ?? '-' }}</td></tr>
                    <tr><th class="text-muted">Department</th><td>{{ $certificate->employee->department?->name ?? '-' }}</td></tr>
                </table>
            </div>
            <div class="col-md-6 text-end">
                <span class="badge fs-6 bg-{{ $certificate->status === 'approved' ? 'success' : 'warning text-dark' }} p-3">
                    {{ strtoupper($certificate->status) }}
                </span>
            </div>
        </div>

        @if($pendingAssets->isEmpty())
        <div class="alert alert-success">
            <i class="fa fa-check-circle me-2"></i>
            This is to certify that <strong>{{ $certificate->employee->full_name }}</strong> has returned all company assets and has no pending dues with the organization.
        </div>
        @else
        <div class="alert alert-warning">
            <strong>Pending Assets — Certificate cannot be approved until all assets are returned.</strong>
        </div>
        @endif

        @if($certificate->remarks)
            <p><strong>Remarks:</strong> {{ $certificate->remarks }}</p>
        @endif

        <div class="row mt-5">
            <div class="col-4 text-center"><div class="border-top pt-2 text-muted small">Employee Signature</div></div>
            <div class="col-4 text-center"><div class="border-top pt-2 text-muted small">HR Manager</div></div>
            <div class="col-4 text-center"><div class="border-top pt-2 text-muted small">Authorized Signatory</div></div>
        </div>
    </div>
</div>
@endsection
@push('styles')
<style>@media print { #topbar,#sidebar,.page-card:first-child,footer{display:none!important} #main-content{margin-left:0!important} }</style>
@endpush
