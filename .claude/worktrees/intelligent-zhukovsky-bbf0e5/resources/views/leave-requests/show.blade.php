@extends('layouts.app')
@section('title','Leave Request')
@section('breadcrumb')
<li class="breadcrumb-item"><a href="{{ route('leave-requests.index') }}" class="text-decoration-none">Leave Requests</a></li>
<li class="breadcrumb-item active">#{{ $leaveRequest->id }}</li>
@endsection

@section('content')
<div class="row g-4">
    {{-- Request Details --}}
    <div class="col-lg-8">
        <div class="card page-card">
            <div class="card-header bg-white py-3 d-flex align-items-center justify-content-between">
                <div class="d-flex align-items-center gap-2">
                    <a href="{{ route('leave-requests.index') }}" class="btn btn-sm btn-outline-secondary"><i class="fa fa-arrow-left"></i></a>
                    <h5 class="mb-0 fw-semibold">Leave Request #{{ $leaveRequest->id }}</h5>
                </div>
                {!! $leaveRequest->status_badge !!}
            </div>
            <div class="card-body">
                <table class="table table-borderless table-sm">
                    <tr><th class="text-muted" style="width:160px">Employee</th><td><strong>{{ $leaveRequest->employee->full_name }}</strong> ({{ $leaveRequest->employee->employee_code }})</td></tr>
                    <tr><th class="text-muted">Department</th><td>{{ $leaveRequest->employee->department?->name ?? '-' }}</td></tr>
                    <tr><th class="text-muted">Designation</th><td>{{ $leaveRequest->employee->designation?->name ?? '-' }}</td></tr>
                    <tr><th class="text-muted">Leave Type</th><td>{{ $leaveRequest->leaveType->name }}</td></tr>
                    <tr><th class="text-muted">From</th><td>{{ $leaveRequest->start_date->format('d M Y') }}</td></tr>
                    <tr><th class="text-muted">To</th><td>{{ $leaveRequest->end_date->format('d M Y') }}</td></tr>
                    <tr><th class="text-muted">Days Requested</th><td><strong>{{ $leaveRequest->days_requested }}</strong></td></tr>
                    <tr><th class="text-muted">Reason</th><td>{{ $leaveRequest->reason ?? '-' }}</td></tr>
                    @if($leaveRequest->status !== 'pending')
                    <tr><th class="text-muted">{{ ucfirst($leaveRequest->status) }} By</th><td>{{ $leaveRequest->approvedBy?->name ?? '-' }}</td></tr>
                    <tr><th class="text-muted">{{ ucfirst($leaveRequest->status) }} At</th><td>{{ $leaveRequest->approved_at?->format('d M Y H:i') ?? '-' }}</td></tr>
                    @if($leaveRequest->remarks)
                    <tr><th class="text-muted">Admin Remarks</th><td>{{ $leaveRequest->remarks }}</td></tr>
                    @endif
                    @endif
                </table>

                {{-- Balance Info --}}
                @if($balance)
                <div class="alert alert-info mt-3">
                    <i class="fa fa-info-circle me-2"></i>
                    <strong>{{ $leaveRequest->leaveType->name }} Balance ({{ now()->year }}):</strong>
                    {{ $balance->remaining_days }} days remaining of {{ $balance->total_days }} total
                    ({{ $balance->used_days }} used)
                </div>
                @endif
            </div>
        </div>
    </div>

    {{-- Actions --}}
    @if($leaveRequest->status === 'pending')
    <div class="col-lg-4">
        <div class="card page-card">
            <div class="card-header bg-white py-3">
                <h6 class="mb-0 fw-semibold">Actions</h6>
            </div>
            <div class="card-body">
                {{-- Approve --}}
                <form action="{{ route('leave-requests.approve', $leaveRequest) }}" method="POST" class="mb-3">
                    @csrf
                    <div class="mb-2">
                        <label class="form-label small">Approval Remarks (optional)</label>
                        <textarea name="remarks" class="form-control form-control-sm" rows="2" placeholder="e.g. Approved, enjoy your leave."></textarea>
                    </div>
                    <button type="submit" class="btn btn-success w-100">
                        <i class="fa fa-check me-1"></i>Approve Request
                    </button>
                </form>
                <hr>
                {{-- Reject --}}
                <form action="{{ route('leave-requests.reject', $leaveRequest) }}" method="POST">
                    @csrf
                    <div class="mb-2">
                        <label class="form-label small">Rejection Reason <span class="text-danger">*</span></label>
                        <textarea name="remarks" class="form-control form-control-sm" rows="2" placeholder="Reason for rejection..." required></textarea>
                    </div>
                    <button type="submit" class="btn btn-danger w-100">
                        <i class="fa fa-xmark me-1"></i>Reject Request
                    </button>
                </form>
            </div>
        </div>
    </div>
    @endif
</div>

@if(session('success'))
<div class="alert alert-success mt-3 alert-dismissible fade show">{{ session('success') }}<button class="btn-close" data-bs-dismiss="alert"></button></div>
@endif
@if(session('error'))
<div class="alert alert-danger mt-3 alert-dismissible fade show">{{ session('error') }}<button class="btn-close" data-bs-dismiss="alert"></button></div>
@endif
@endsection
