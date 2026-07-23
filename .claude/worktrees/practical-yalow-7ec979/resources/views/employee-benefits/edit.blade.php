@extends('layouts.app')
@section('title', 'Edit Benefit')
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('employee-benefits.index') }}" class="text-decoration-none">Employee Benefits</a></li>
    <li class="breadcrumb-item active">Edit</li>
@endsection

@section('content')
<div class="card page-card" style="max-width:800px">
    <div class="card-header bg-white py-3">
        <h5 class="mb-0 fw-semibold"><i class="fa fa-pen me-2 text-primary"></i>Edit Benefit</h5>
    </div>
    <div class="card-body">
        @if($errors->any())
        <div class="alert alert-danger"><ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>
        @endif

        <div class="alert alert-info small mb-3">
            <i class="fa fa-info-circle me-1"></i>
            <strong>Note:</strong> Changes to active benefits apply to future payroll months only. Existing salary slips remain unchanged.
        </div>

        <form action="{{ route('employee-benefits.update', $employeeBenefit) }}" method="POST">
            @csrf @method('PUT')
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label fw-semibold">Employee <span class="text-danger">*</span></label>
                    <select name="employee_id" class="form-select @error('employee_id') is-invalid @enderror" required>
                        @foreach($employees as $e)
                            <option value="{{ $e->id }}" {{ old('employee_id', $employeeBenefit->employee_id) == $e->id ? 'selected' : '' }}>
                                {{ $e->full_name }} ({{ $e->employee_code }})
                            </option>
                        @endforeach
                    </select>
                    @error('employee_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-semibold">Benefit Fund Type <span class="text-danger">*</span></label>
                    <select name="benefit_fund_type_id" class="form-select @error('benefit_fund_type_id') is-invalid @enderror" required>
                        @foreach($fundTypes as $t)
                            <option value="{{ $t->id }}" {{ old('benefit_fund_type_id', $employeeBenefit->benefit_fund_type_id) == $t->id ? 'selected' : '' }}>{{ $t->name }}</option>
                        @endforeach
                    </select>
                    @error('benefit_fund_type_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-semibold">Amount (₹) <span class="text-danger">*</span></label>
                    <input type="number" step="0.01" min="0" name="amount" value="{{ old('amount', $employeeBenefit->amount) }}" class="form-control @error('amount') is-invalid @enderror" required>
                    @error('amount')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-semibold">Benefit Name (Optional)</label>
                    <input type="text" name="benefit_name" value="{{ old('benefit_name', $employeeBenefit->benefit_name) }}" maxlength="255" class="form-control @error('benefit_name') is-invalid @enderror" placeholder="e.g., 'Education Fund 2026'">
                    <div class="form-text">Custom name for this benefit. If empty, uses fund type name.</div>
                    @error('benefit_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                {{-- Recurring Benefits Section --}}
                <div class="col-12">
                    <div class="card bg-light border-0 mb-3">
                        <div class="card-header bg-transparent fw-semibold text-muted py-2">
                            <i class="fa fa-repeat me-1"></i>Recurring Benefit (Optional)
                        </div>
                        <div class="card-body pt-3">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label">Start Date</label>
                                    <input type="date" name="start_date" value="{{ old('start_date', $employeeBenefit->start_date?->toDateString()) }}" class="form-control @error('start_date') is-invalid @enderror">
                                    <div class="form-text">When does this recurring benefit start?</div>
                                    @error('start_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">End Date (Optional)</label>
                                    <input type="date" name="end_date" value="{{ old('end_date', $employeeBenefit->end_date?->toDateString()) }}" class="form-control @error('end_date') is-invalid @enderror">
                                    <div class="form-text">Leave empty for indefinite/ongoing benefit.</div>
                                    @error('end_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Frequency</label>
                                    <select name="frequency" class="form-select @error('frequency') is-invalid @enderror">
                                        <option value="">Select Frequency...</option>
                                        <option value="weekly"      {{ old('frequency', $employeeBenefit->frequency) === 'weekly'      ? 'selected' : '' }}>Weekly</option>
                                        <option value="fortnightly" {{ old('frequency', $employeeBenefit->frequency) === 'fortnightly' ? 'selected' : '' }}>Fortnightly (Every 2 weeks)</option>
                                        <option value="monthly"     {{ old('frequency', $employeeBenefit->frequency) === 'monthly'     ? 'selected' : '' }}>Monthly</option>
                                        <option value="quarterly"   {{ old('frequency', $employeeBenefit->frequency) === 'quarterly'   ? 'selected' : '' }}>Quarterly (Every 3 months)</option>
                                        <option value="half_yearly" {{ old('frequency', $employeeBenefit->frequency) === 'half_yearly' ? 'selected' : '' }}>Half-Yearly (Every 6 months)</option>
                                        <option value="annual"      {{ old('frequency', $employeeBenefit->frequency) === 'annual'      ? 'selected' : '' }}>Annual (Yearly)</option>
                                    </select>
                                    <div class="form-text">How often does this benefit recur?</div>
                                    @error('frequency')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Legacy Section --}}
                <div class="col-12">
                    <div class="card bg-light border-0 mb-3">
                        <div class="card-header bg-transparent fw-semibold text-muted py-2">
                            <i class="fa fa-calendar me-1"></i>Legacy Mode (For backward compatibility)
                        </div>
                        <div class="card-body pt-3">
                            <div class="col-md-4">
                                <label class="form-label">Effective Month</label>
                                <input type="month" name="effective_month" value="{{ old('effective_month', $employeeBenefit->effective_month?->format('Y-m')) }}" class="form-control @error('effective_month') is-invalid @enderror">
                                <div class="form-text">Used if no start date is provided.</div>
                                @error('effective_month')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-md-6">
                    <label class="form-label fw-semibold">Status <span class="text-danger">*</span></label>
                    <select name="status" class="form-select @error('status') is-invalid @enderror" required>
                        <option value="active"   {{ old('status', $employeeBenefit->status) === 'active'   ? 'selected' : '' }}>Active</option>
                        <option value="inactive" {{ old('status', $employeeBenefit->status) === 'inactive' ? 'selected' : '' }}>Inactive</option>
                    </select>
                    @error('status')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-12">
                    <label class="form-label fw-semibold">Description / Notes</label>
                    <textarea name="description" rows="2" class="form-control @error('description') is-invalid @enderror" maxlength="1000">{{ old('description', $employeeBenefit->description) }}</textarea>
                    @error('description')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
            </div>

            <div class="mt-4 d-flex gap-2">
                <button class="btn btn-primary"><i class="fa fa-save me-1"></i>Update</button>
                <a href="{{ route('employee-benefits.index') }}" class="btn btn-light">Cancel</a>
            </div>
        </form>
    </div>
</div>
@endsection
