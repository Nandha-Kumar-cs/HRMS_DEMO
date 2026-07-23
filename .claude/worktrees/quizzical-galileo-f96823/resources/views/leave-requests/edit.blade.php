@extends('layouts.app')
@section('title','Edit Leave Request #' . $leaveRequest->id)
@section('breadcrumb')
<li class="breadcrumb-item"><a href="{{ route('leave-requests.index') }}" class="text-decoration-none">Leave Requests</a></li>
<li class="breadcrumb-item"><a href="{{ route('leave-requests.show', $leaveRequest) }}" class="text-decoration-none">#{{ $leaveRequest->id }}</a></li>
<li class="breadcrumb-item active">Edit</li>
@endsection

@section('content')
<div class="row g-4">
    <div class="col-lg-8">
        <div class="card page-card">
            <div class="card-header bg-white py-3 d-flex align-items-center gap-2">
                <a href="{{ route('leave-requests.show', $leaveRequest) }}" class="btn btn-sm btn-outline-secondary">
                    <i class="fa fa-arrow-left"></i>
                </a>
                <h5 class="mb-0 fw-semibold">Edit Leave Request #{{ $leaveRequest->id }}</h5>
                <span class="ms-2">{!! $leaveRequest->status_badge !!}</span>
            </div>

            <div class="card-body">

                @if($leaveRequest->status === 'approved')
                <div class="alert alert-warning d-flex align-items-start gap-2 mb-4">
                    <i class="fa fa-triangle-exclamation mt-1"></i>
                    <div>
                        <strong>This request is currently Approved.</strong><br>
                        Saving changes will automatically:
                        <ul class="mb-0 mt-1">
                            <li>Reverse the leave balance deduction</li>
                            <li>Revert attendance records from <em>On Leave</em> back to <em>Absent</em></li>
                            <li>Re-apply if you keep status as Approved (new dates/type)</li>
                        </ul>
                    </div>
                </div>
                @endif

                @if($errors->any())
                    <div class="alert alert-danger"><ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>
                @endif

                <form action="{{ route('leave-requests.update', $leaveRequest) }}" method="POST">
                    @csrf
                    @method('PUT')

                    {{-- Employee --}}
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Employee <span class="text-danger">*</span></label>
                        <select name="employee_id" class="form-select @error('employee_id') is-invalid @enderror" required>
                            <option value="">Select Employee</option>
                            @foreach($employees as $emp)
                                <option value="{{ $emp->id }}"
                                    {{ old('employee_id', $leaveRequest->employee_id) == $emp->id ? 'selected' : '' }}>
                                    {{ $emp->full_name }} ({{ $emp->employee_code }})
                                </option>
                            @endforeach
                        </select>
                        @error('employee_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    {{-- Leave Type --}}
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Leave Type <span class="text-danger">*</span></label>
                        <select name="leave_type_id" class="form-select @error('leave_type_id') is-invalid @enderror" required>
                            <option value="">Select Leave Type</option>
                            @foreach($leaveTypes as $lt)
                                <option value="{{ $lt->id }}"
                                    {{ old('leave_type_id', $leaveRequest->leave_type_id) == $lt->id ? 'selected' : '' }}>
                                    {{ $lt->name }}
                                    <span class="text-muted">({{ $lt->is_paid ? 'Paid' : 'Unpaid' }}, {{ $lt->days_allowed }} days/yr)</span>
                                </option>
                            @endforeach
                        </select>
                        @error('leave_type_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    {{-- Dates --}}
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Start Date <span class="text-danger">*</span></label>
                            <input type="date" name="start_date"
                                class="form-control @error('start_date') is-invalid @enderror"
                                value="{{ old('start_date', $leaveRequest->start_date->format('Y-m-d')) }}" required>
                            @error('start_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">End Date <span class="text-danger">*</span></label>
                            <input type="date" name="end_date"
                                class="form-control @error('end_date') is-invalid @enderror"
                                value="{{ old('end_date', $leaveRequest->end_date->format('Y-m-d')) }}" required>
                            @error('end_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>

                    {{-- Reason --}}
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Reason</label>
                        <textarea name="reason" class="form-control" rows="3"
                            placeholder="Optional reason">{{ old('reason', $leaveRequest->reason) }}</textarea>
                    </div>

                    {{-- Status --}}
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Status <span class="text-danger">*</span></label>
                        <select name="status" class="form-select @error('status') is-invalid @enderror" required id="statusSelect">
                            <option value="pending"  {{ old('status', $leaveRequest->status) === 'pending'  ? 'selected' : '' }}>⏳ Pending</option>
                            <option value="approved" {{ old('status', $leaveRequest->status) === 'approved' ? 'selected' : '' }}>✅ Approved</option>
                            <option value="rejected" {{ old('status', $leaveRequest->status) === 'rejected' ? 'selected' : '' }}>❌ Rejected</option>
                        </select>
                        @error('status')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    {{-- Admin Remarks (shown when not pending) --}}
                    <div class="mb-4" id="remarksRow">
                        <label class="form-label fw-semibold">Admin Remarks</label>
                        <textarea name="remarks" class="form-control" rows="2"
                            placeholder="Optional remarks for approval/rejection">{{ old('remarks', $leaveRequest->remarks) }}</textarea>
                    </div>

                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="fa fa-save me-1"></i>Save Changes
                        </button>
                        <a href="{{ route('leave-requests.show', $leaveRequest) }}" class="btn btn-outline-secondary">
                            Cancel
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- Info panel --}}
    <div class="col-lg-4">
        <div class="card page-card">
            <div class="card-header bg-white py-3">
                <h6 class="mb-0 fw-semibold"><i class="fa fa-circle-info me-2 text-primary"></i>Current Details</h6>
            </div>
            <div class="card-body small">
                <table class="table table-sm table-borderless mb-0">
                    <tr><th class="text-muted">Employee</th><td>{{ $leaveRequest->employee->full_name }}</td></tr>
                    <tr><th class="text-muted">Leave Type</th><td>{{ $leaveRequest->leaveType->name }}</td></tr>
                    <tr><th class="text-muted">Period</th>
                        <td>{{ $leaveRequest->start_date->format('d M Y') }}
                        @if($leaveRequest->start_date->ne($leaveRequest->end_date))
                            – {{ $leaveRequest->end_date->format('d M Y') }}
                        @endif
                        </td>
                    </tr>
                    <tr><th class="text-muted">Days</th><td><strong>{{ $leaveRequest->days_requested }}</strong></td></tr>
                    <tr><th class="text-muted">Status</th><td>{!! $leaveRequest->status_badge !!}</td></tr>
                    @if($leaveRequest->status !== 'pending')
                    <tr><th class="text-muted">{{ ucfirst($leaveRequest->status) }} By</th><td>{{ $leaveRequest->approvedBy?->name ?? '—' }}</td></tr>
                    <tr><th class="text-muted">{{ ucfirst($leaveRequest->status) }} At</th><td>{{ $leaveRequest->approved_at?->format('d M Y H:i') ?? '—' }}</td></tr>
                    @endif
                </table>
            </div>
        </div>

        <div class="card page-card mt-3 border-warning">
            <div class="card-body small text-muted">
                <i class="fa fa-lightbulb text-warning me-1"></i>
                <strong>Quick tip:</strong><br>
                To undo an accidental approval, change the <strong>Status</strong> back to
                <em>Pending</em> and save. The leave balance and attendance records will
                be automatically reversed.
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
// Show/hide remarks based on status
const statusSel = document.getElementById('statusSelect');
const remarksRow = document.getElementById('remarksRow');
function toggleRemarks() {
    remarksRow.style.display = statusSel.value === 'pending' ? 'none' : '';
}
statusSel.addEventListener('change', toggleRemarks);
toggleRemarks();
</script>
@endpush
@endsection
