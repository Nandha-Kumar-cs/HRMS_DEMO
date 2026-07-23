@extends('layouts.app')
@section('title', 'Edit Bonus')
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('employee-bonuses.index') }}" class="text-decoration-none">Bonuses</a></li>
    <li class="breadcrumb-item active">Edit</li>
@endsection

@section('content')
<div class="card page-card" style="max-width:760px">
    <div class="card-header bg-white py-3">
        <h5 class="mb-0 fw-semibold"><i class="fa fa-pen me-2 text-primary"></i>Edit Bonus</h5>
    </div>
    <div class="card-body">
        @if($errors->any())
        <div class="alert alert-danger"><ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>
        @endif

        <form action="{{ route('employee-bonuses.update', $employeeBonus) }}" method="POST">
            @csrf @method('PUT')
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label fw-semibold">Employee <span class="text-danger">*</span></label>
                    <select name="employee_id" class="form-select" required>
                        @foreach($employees as $e)
                            <option value="{{ $e->id }}" {{ old('employee_id', $employeeBonus->employee_id) == $e->id ? 'selected' : '' }}>{{ $e->full_name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-semibold">Type <span class="text-danger">*</span></label>
                    <select name="type" class="form-select" required>
                        @foreach(\App\Models\EmployeeBonus::TYPES as $k => $label)
                            <option value="{{ $k }}" {{ old('type', $employeeBonus->type) === $k ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-semibold">Amount (₹) <span class="text-danger">*</span></label>
                    <input type="number" step="0.01" min="0" name="amount" value="{{ old('amount', $employeeBonus->amount) }}" class="form-control" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-semibold">Payroll Month <span class="text-danger">*</span></label>
                    <select name="payroll_month" class="form-select" required>
                        @for($m = 1; $m <= 12; $m++)
                        <option value="{{ $m }}" {{ old('payroll_month', $employeeBonus->payroll_month) == $m ? 'selected' : '' }}>{{ date('F', mktime(0,0,0,$m,1)) }}</option>
                        @endfor
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-semibold">Payroll Year <span class="text-danger">*</span></label>
                    <select name="payroll_year" class="form-select" required>
                        @for($y = now()->year + 1; $y >= now()->year - 4; $y--)
                        <option value="{{ $y }}" {{ old('payroll_year', $employeeBonus->payroll_year) == $y ? 'selected' : '' }}>{{ $y }}</option>
                        @endfor
                    </select>
                </div>
                <div class="col-12">
                    <label class="form-label fw-semibold">Reason <span class="text-danger">*</span></label>
                    <input type="text" name="reason" value="{{ old('reason', $employeeBonus->reason) }}" class="form-control" maxlength="255" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-semibold">Status <span class="text-danger">*</span></label>
                    <select name="status" class="form-select" required>
                        <option value="approved" {{ old('status', $employeeBonus->status) === 'approved' ? 'selected' : '' }}>Approved</option>
                        <option value="pending"  {{ old('status', $employeeBonus->status) === 'pending'  ? 'selected' : '' }}>Pending</option>
                        <option value="rejected" {{ old('status', $employeeBonus->status) === 'rejected' ? 'selected' : '' }}>Rejected</option>
                    </select>
                </div>
                <div class="col-12">
                    <label class="form-label fw-semibold">Remarks</label>
                    <textarea name="remarks" rows="2" class="form-control" maxlength="1000">{{ old('remarks', $employeeBonus->remarks) }}</textarea>
                </div>
            </div>

            <div class="mt-4 d-flex gap-2">
                <button class="btn btn-primary"><i class="fa fa-save me-1"></i>Update</button>
                <a href="{{ route('employee-bonuses.index') }}" class="btn btn-light">Cancel</a>
            </div>
        </form>
    </div>
</div>
@endsection
