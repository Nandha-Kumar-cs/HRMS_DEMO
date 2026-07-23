@extends('layouts.app')
@section('title','New Leave Request')
@section('breadcrumb')
<li class="breadcrumb-item"><a href="{{ route('leave-requests.index') }}" class="text-decoration-none">Leave Requests</a></li>
<li class="breadcrumb-item active">New Request</li>
@endsection

@section('content')
<div class="card page-card" style="max-width:600px">
    <div class="card-header bg-white py-3 d-flex align-items-center gap-2">
        <a href="{{ route('leave-requests.index') }}" class="btn btn-sm btn-outline-secondary"><i class="fa fa-arrow-left"></i></a>
        <h5 class="mb-0 fw-semibold">New Leave Request</h5>
    </div>
    <div class="card-body">
        @if($errors->any())
            <div class="alert alert-danger"><ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>
        @endif
        <form action="{{ route('leave-requests.store') }}" method="POST">
            @csrf
            <div class="mb-3">
                <label class="form-label">Employee <span class="text-danger">*</span></label>
                <select name="employee_id" class="form-select @error('employee_id') is-invalid @enderror" required>
                    <option value="">Select Employee</option>
                    @foreach($employees as $e)
                        <option value="{{ $e->id }}" {{ old('employee_id') == $e->id ? 'selected' : '' }}>
                            {{ $e->full_name }} ({{ $e->employee_code }})
                        </option>
                    @endforeach
                </select>
                @error('employee_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="mb-3">
                <label class="form-label">Leave Type <span class="text-danger">*</span></label>
                <select name="leave_type_id" class="form-select @error('leave_type_id') is-invalid @enderror" required>
                    <option value="">Select Leave Type</option>
                    @foreach($leaveTypes as $lt)
                        <option value="{{ $lt->id }}" {{ old('leave_type_id') == $lt->id ? 'selected' : '' }}>
                            {{ $lt->name }} ({{ $lt->days_allowed }} days/yr)
                        </option>
                    @endforeach
                </select>
                @error('leave_type_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="row g-3 mb-3">
                <div class="col-md-6">
                    <label class="form-label">Start Date <span class="text-danger">*</span></label>
                    <input type="date" name="start_date" class="form-control @error('start_date') is-invalid @enderror"
                        value="{{ old('start_date') }}" required>
                    @error('start_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-6">
                    <label class="form-label">End Date <span class="text-danger">*</span></label>
                    <input type="date" name="end_date" class="form-control @error('end_date') is-invalid @enderror"
                        value="{{ old('end_date') }}" required>
                    @error('end_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
            </div>
            <div class="mb-4">
                <label class="form-label">Reason</label>
                <textarea name="reason" class="form-control" rows="3" placeholder="Optional reason">{{ old('reason') }}</textarea>
            </div>
            <button type="submit" class="btn btn-primary"><i class="fa fa-paper-plane me-1"></i>Submit Request</button>
        </form>
    </div>
</div>
@endsection
