@extends('layouts.app')
@section('title', 'Add Bonus')
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('employee-bonuses.index') }}" class="text-decoration-none">Bonuses</a></li>
    <li class="breadcrumb-item active">Add</li>
@endsection

@section('content')
<div class="card page-card" style="max-width:760px">
    <div class="card-header bg-white py-3">
        <h5 class="mb-0 fw-semibold"><i class="fa fa-trophy me-2 text-warning"></i>Add Bonus / Incentive</h5>
    </div>
    <div class="card-body">
        @if($errors->any())
        <div class="alert alert-danger"><ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>
        @endif

        <form action="{{ route('employee-bonuses.store') }}" method="POST">
            @csrf
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label fw-semibold">Employee <span class="text-danger">*</span></label>
                    <select name="employee_id" class="form-select" required>
                        <option value="">Select Employee</option>
                        @foreach($employees as $e)
                            <option value="{{ $e->id }}" {{ (old('employee_id', $selectedEmployeeId) == $e->id) ? 'selected' : '' }}>
                                {{ $e->full_name }} ({{ $e->employee_code }})
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-semibold">Type <span class="text-danger">*</span></label>
                    <select name="type" class="form-select" required>
                        @foreach(\App\Models\EmployeeBonus::TYPES as $k => $label)
                            <option value="{{ $k }}" {{ old('type') === $k ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-semibold">Amount (₹) <span class="text-danger">*</span></label>
                    <input type="number" step="0.01" min="0" name="amount" value="{{ old('amount') }}" class="form-control" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-semibold">Payroll Month <span class="text-danger">*</span></label>
                    <select name="payroll_month" class="form-select" required>
                        @for($m = 1; $m <= 12; $m++)
                        <option value="{{ $m }}" {{ old('payroll_month', now()->month) == $m ? 'selected' : '' }}>{{ date('F', mktime(0,0,0,$m,1)) }}</option>
                        @endfor
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-semibold">Payroll Year <span class="text-danger">*</span></label>
                    <select name="payroll_year" class="form-select" required>
                        @for($y = now()->year + 1; $y >= now()->year - 4; $y--)
                        <option value="{{ $y }}" {{ old('payroll_year', now()->year) == $y ? 'selected' : '' }}>{{ $y }}</option>
                        @endfor
                    </select>
                </div>
                <div class="col-12">
                    <label class="form-label fw-semibold">Reason <span class="text-danger">*</span></label>
                    <input type="text" name="reason" value="{{ old('reason') }}" class="form-control" maxlength="255" required>
                    <div class="form-text">e.g. "Diwali Bonus", "Q4 Performance Excellence", "Project Delivery"</div>
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-semibold">Status <span class="text-danger">*</span></label>
                    <select name="status" class="form-select" required>
                        <option value="approved" {{ old('status', 'approved') === 'approved' ? 'selected' : '' }}>Approved (apply to payslip)</option>
                        <option value="pending"  {{ old('status') === 'pending'  ? 'selected' : '' }}>Pending</option>
                        <option value="rejected" {{ old('status') === 'rejected' ? 'selected' : '' }}>Rejected</option>
                    </select>
                </div>
                <div class="col-12">
                    <label class="form-label fw-semibold">Remarks</label>
                    <textarea name="remarks" rows="2" class="form-control" maxlength="1000">{{ old('remarks') }}</textarea>
                </div>
            </div>

            <div class="mt-4 d-flex gap-2">
                <button class="btn btn-primary"><i class="fa fa-save me-1"></i>Save</button>
                <a href="{{ route('employee-bonuses.index') }}" class="btn btn-light">Cancel</a>
            </div>
        </form>
    </div>
</div>
@endsection
