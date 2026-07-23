@extends('layouts.app')
@section('title', 'Generate Salary Slip')
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('salary-slips.index') }}" class="text-decoration-none">Salary Slips</a></li>
    <li class="breadcrumb-item active">Generate</li>
@endsection
@section('content')
<div class="card page-card" style="max-width:700px">
    <div class="card-header bg-white py-3 d-flex align-items-center gap-2">
        <a href="{{ route('salary-slips.index') }}" class="btn btn-sm btn-outline-secondary"><i class="fa fa-arrow-left"></i></a>
        <h5 class="mb-0 fw-semibold">Generate Salary Slip</h5>
    </div>
    <div class="card-body">
        @include('partials.salary-guard-alert')

        {{-- ── Slip Already Exists Warning ─────────────────────────────────── --}}
        @if(session('slip_exists'))
        @php $ex = session('slip_exists'); @endphp
        <div class="alert alert-warning border-warning shadow-sm mb-4" role="alert">
            <div class="d-flex align-items-start gap-3">
                <i class="fa fa-triangle-exclamation fa-lg text-warning mt-1"></i>
                <div class="flex-grow-1">
                    <div class="fw-bold mb-1">Payslip Already Exists</div>
                    <p class="mb-2 small">
                        A salary slip for <strong>{{ $ex['name'] }}</strong> for
                        <strong>{{ $ex['month_name'] }} {{ $ex['year'] }}</strong>
                        has already been generated.
                        Net Salary on record: <strong class="text-success">₹{{ number_format($ex['net'], 2) }}</strong>
                    </p>
                    <div class="d-flex gap-2 flex-wrap">
                        {{-- View existing --}}
                        <a href="{{ route('salary-slips.show', $ex['slip_id']) }}"
                           class="btn btn-sm btn-outline-primary">
                            <i class="fa fa-eye me-1"></i>View Existing Payslip
                        </a>
                        {{-- Regenerate (force overwrite) --}}
                        <form action="{{ route('salary-slips.store') }}" method="POST" class="d-inline"
                              onsubmit="return confirm('This will recalculate and overwrite the existing payslip for {{ $ex['name'] }} ({{ $ex['month_name'] }} {{ $ex['year'] }}). Loan deductions will NOT be re-processed.\n\nProceed?')">
                            @csrf
                            <input type="hidden" name="employee_id"      value="{{ $ex['employee_id'] ?? old('employee_id') }}">
                            <input type="hidden" name="month"            value="{{ old('month', date('n', mktime(0,0,0,array_search($ex['month_name'], ['','January','February','March','April','May','June','July','August','September','October','November','December']),1))) }}">
                            <input type="hidden" name="year"             value="{{ $ex['year'] }}">
                            <input type="hidden" name="force_regenerate" value="1">
                            <button type="submit" class="btn btn-sm btn-warning text-dark">
                                <i class="fa fa-rotate me-1"></i>Regenerate Payslip
                            </button>
                        </form>
                        {{-- Dismiss --}}
                        <a href="{{ route('salary-slips.create') }}" class="btn btn-sm btn-light">
                            <i class="fa fa-xmark me-1"></i>Dismiss
                        </a>
                    </div>
                    <p class="text-muted small mt-2 mb-0">
                        <i class="fa fa-info-circle me-1"></i>
                        <em>Regenerating will recalculate salary, attendance deductions, OT and Late — using today's salary components and current attendance data.</em>
                    </p>
                </div>
            </div>
        </div>
        @endif

        {{-- ── Generate Form ───────────────────────────────────────────────── --}}
        <form action="{{ route('salary-slips.store') }}" method="POST">
            @csrf
            @if($errors->any())
                <div class="alert alert-danger"><ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>
            @endif
            <div class="row g-3">
                <div class="col-md-5">
                    <label class="form-label">Employee <span class="text-danger">*</span></label>
                    <select name="employee_id" id="employeeSelect" class="form-select @error('employee_id') is-invalid @enderror">
                        <option value="">Select Employee</option>
                        @foreach($employees as $emp)
                            <option value="{{ $emp->id }}"
                                data-salary="{{ $emp->fixed_salary + $emp->variable_salary }}"
                                data-fixed="{{ $emp->fixed_salary }}"
                                data-name="{{ $emp->full_name }}"
                                data-edit="{{ route('employees.edit', $emp) }}"
                                {{ (old('employee_id', $selected?->id) == $emp->id) ? 'selected' : '' }}>
                                {{ $emp->full_name }} ({{ $emp->employee_code }})
                            </option>
                        @endforeach
                    </select>
                    @error('employee_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-4">
                    <label class="form-label">Month <span class="text-danger">*</span></label>
                    <select name="month" class="form-select @error('month') is-invalid @enderror">
                        @for($m = 1; $m <= 12; $m++)
                            <option value="{{ $m }}" {{ old('month', date('n')) == $m ? 'selected' : '' }}>{{ date('F', mktime(0,0,0,$m,1)) }}</option>
                        @endfor
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Year <span class="text-danger">*</span></label>
                    <select name="year" class="form-select @error('year') is-invalid @enderror">
                        @for($y = date('Y'); $y >= date('Y') - 5; $y--)
                            <option value="{{ $y }}" {{ old('year', date('Y')) == $y ? 'selected' : '' }}>{{ $y }}</option>
                        @endfor
                    </select>
                </div>
            </div>

            {{-- Salary preview card --}}
            <div id="salaryPreview" class="d-none mt-3">
                <div class="card bg-light border-0">
                    <div class="card-body py-2">
                        <div class="row text-center">
                            <div class="col">
                                <div class="text-muted small">CTC / Month</div>
                                <div class="fw-bold text-success" id="previewFixed">₹0</div>
                            </div>
                            <div class="col">
                                <div class="text-muted small">Variable Pay</div>
                                <div class="fw-bold text-info" id="previewVariable">₹0</div>
                            </div>
                            <div class="col">
                                <div class="text-muted small">Total Package</div>
                                <div class="fw-bold text-primary" id="previewCTC">₹0</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="mt-4 d-flex gap-2">
                <button type="submit" class="btn btn-primary"><i class="fa fa-calculator me-1"></i>Generate Salary Slip</button>
                <a href="{{ route('salary-slips.index') }}" class="btn btn-light">Cancel</a>
            </div>
        </form>
    </div>
</div>
@endsection
@push('scripts')
<script>
function checkSalary(sel) {
    var opt      = sel.find(':selected');
    var salary   = parseFloat(opt.data('salary') || 0);
    var fixed    = parseFloat(opt.data('fixed') || 0);
    var variable = salary - fixed;
    if (sel.val()) {
        if (salary === 0) {
            $('#salaryWarningName').text(opt.data('name') || '');
            $('#salaryWarningLink').attr('href', opt.data('edit') || '#');
            $('#salaryWarningBanner').removeClass('d-none');
            $('#salaryPreview').addClass('d-none');
        } else {
            $('#salaryWarningBanner').addClass('d-none');
            $('#previewFixed').text('₹' + fixed.toLocaleString('en-IN', {minimumFractionDigits:2}));
            $('#previewVariable').text('₹' + variable.toLocaleString('en-IN', {minimumFractionDigits:2}));
            $('#previewCTC').text('₹' + salary.toLocaleString('en-IN', {minimumFractionDigits:2}));
            $('#salaryPreview').removeClass('d-none');
        }
    } else {
        $('#salaryWarningBanner').addClass('d-none');
        $('#salaryPreview').addClass('d-none');
    }
}
$(function(){
    $('#employeeSelect').on('change', function(){ checkSalary($(this)); });
    if ($('#employeeSelect').val()) checkSalary($('#employeeSelect'));
});
</script>
@endpush
