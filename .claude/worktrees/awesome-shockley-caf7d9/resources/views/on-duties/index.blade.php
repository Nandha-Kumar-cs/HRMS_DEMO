@extends('layouts.app')
@section('title', 'On Duty (OD) Management')
@section('breadcrumb')
    <li class="breadcrumb-item active">On Duty (OD)</li>
@endsection

@section('content')


<div class="row g-4">

    {{-- Left: OD records for selected month --}}
    <div class="col-lg-8">
        <div class="card page-card">
            <div class="card-header bg-white py-3 d-flex align-items-center justify-content-between flex-wrap gap-2">
                <h5 class="mb-0 fw-semibold">
                    <i class="fa fa-briefcase-clock me-2" style="color:#0891b2"></i>On Duty Records
                </h5>
                <form method="GET" class="d-flex gap-2 align-items-center">
                    <select name="month" class="form-select form-select-sm">
                        @for($m = 1; $m <= 12; $m++)
                            <option value="{{ $m }}" {{ $m == $month ? 'selected' : '' }}>
                                {{ date('F', mktime(0,0,0,$m,1)) }}
                            </option>
                        @endfor
                    </select>
                    <select name="year" class="form-select form-select-sm" style="width:90px">
                        @for($y = now()->year + 1; $y >= now()->year - 2; $y--)
                            <option value="{{ $y }}" {{ $y == $year ? 'selected' : '' }}>{{ $y }}</option>
                        @endfor
                    </select>
                    <button type="submit" class="btn btn-sm btn-primary">Go</button>
                </form>
            </div>

            <div class="card-body">
                <div class="alert alert-info border-info d-flex align-items-start gap-2 py-2 mb-3 small">
                    <i class="fa fa-circle-info mt-1 text-info flex-shrink-0"></i>
                    <div>
                        <strong>On Duty (OD)</strong> is for employees working at a client site, field, or off-site location.
                        OD days are treated as <strong>Present</strong> — no salary deduction applies.
                        Attendance is auto-marked as <span class="badge" style="background:#0891b2">OD</span> on the assigned date.
                    </div>
                </div>

                @if($odRecords->isEmpty())
                    <div class="text-center py-5 text-muted">
                        <i class="fa fa-briefcase-clock fa-3x d-block mb-3 opacity-25"></i>
                        <p class="mb-0">No OD records for {{ date('F', mktime(0,0,0,$month,1)) }} {{ $year }}.</p>
                    </div>
                @else
                    @foreach($odRecords as $dateStr => $group)
                    @php $odDate = \Carbon\Carbon::parse($dateStr); @endphp
                    <div class="mb-4">
                        <div class="d-flex align-items-center gap-2 mb-2">
                            <span class="badge py-2 px-3" style="background:#0891b2;font-size:.82rem">
                                <i class="fa fa-calendar me-1"></i>{{ $odDate->format('d M Y') }} — {{ $odDate->format('l') }}
                            </span>
                            <span class="badge bg-primary">{{ $group->count() }} employee(s)</span>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-bordered table-sm align-middle mb-0" style="font-size:.88rem">
                                <thead class="table-light">
                                    <tr>
                                        <th>Employee</th>
                                        <th>Reason / Remarks</th>
                                        <th class="text-center" style="width:100px">Assigned By</th>
                                        <th class="text-center" style="width:70px">Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($group as $od)
                                    <tr>
                                        <td>
                                            <strong>{{ $od->employee->full_name }}</strong>
                                            <small class="text-muted ms-1">{{ $od->employee->employee_code }}</small>
                                        </td>
                                        <td class="text-muted">{{ $od->reason ?: '—' }}</td>
                                        <td class="text-center text-muted small">
                                            {{ $od->createdBy?->name ?? 'System' }}
                                        </td>
                                        <td class="text-center">
                                            <form action="{{ route('on-duties.destroy', $od) }}" method="POST"
                                                  onsubmit="return confirm('Remove OD for {{ $od->employee->full_name }} on {{ $odDate->format('d M Y') }}?')">
                                                @csrf @method('DELETE')
                                                <button type="submit" class="btn btn-sm btn-outline-danger" title="Remove">
                                                    <i class="fa fa-trash"></i>
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                    @endforeach
                @endif
            </div>
        </div>
    </div>

    {{-- Right: Assign OD form --}}
    <div class="col-lg-4">
        <div class="card page-card">
            <div class="card-header bg-white py-3">
                <h6 class="mb-0 fw-semibold">
                    <i class="fa fa-plus-circle me-2" style="color:#0891b2"></i>Assign On Duty
                </h6>
            </div>
            <div class="card-body">
                @if($errors->any())
                <div class="alert alert-danger small py-2">
                    <ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
                </div>
                @endif

                <form action="{{ route('on-duties.store') }}" method="POST">
                    @csrf

                    <div class="mb-3">
                        <label class="form-label fw-semibold">
                            Date <span class="text-danger">*</span>
                        </label>
                        <input type="date" name="date"
                               class="form-control @error('date') is-invalid @enderror"
                               value="{{ old('date', today()->toDateString()) }}" required>
                        @error('date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="mb-3">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <label class="form-label fw-semibold mb-0">
                                Employee(s) <span class="text-danger">*</span>
                            </label>
                            <div class="d-flex gap-2">
                                <a href="#" id="selectAllEmp" class="small text-decoration-none">All</a>
                                <span class="text-muted small">|</span>
                                <a href="#" id="clearAllEmp" class="small text-decoration-none">Clear</a>
                            </div>
                        </div>
                        <div class="border rounded p-2" style="max-height:260px;overflow-y:auto">
                            @foreach($employees as $emp)
                            <div class="form-check">
                                <input class="form-check-input emp-check"
                                       type="checkbox"
                                       name="employee_ids[]"
                                       value="{{ $emp->id }}"
                                       id="emp_{{ $emp->id }}"
                                       {{ in_array($emp->id, old('employee_ids', [])) ? 'checked' : '' }}>
                                <label class="form-check-label" for="emp_{{ $emp->id }}" style="font-size:.88rem">
                                    {{ $emp->full_name }}
                                    <small class="text-muted">{{ $emp->employee_code }}</small>
                                </label>
                            </div>
                            @endforeach
                        </div>
                        @error('employee_ids')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                    </div>

                    <div class="mb-4">
                        <label class="form-label fw-semibold">Reason / Remarks</label>
                        <input type="text" name="reason"
                               class="form-control"
                               placeholder="e.g. Client site visit, Field work"
                               value="{{ old('reason') }}" maxlength="255">
                    </div>

                    <button type="submit" class="btn w-100 text-white fw-semibold" style="background:#0891b2">
                        <i class="fa fa-check me-1"></i>Assign OD
                    </button>
                </form>
            </div>
        </div>

        {{-- Legend --}}
        <div class="card page-card mt-3">
            <div class="card-body py-3">
                <div class="small fw-semibold mb-2 text-muted">How OD affects records:</div>
                <ul class="small text-muted mb-0 ps-3">
                    <li>Attendance marked as <span class="badge" style="background:#0891b2;font-size:.7rem">OD</span> on the date</li>
                    <li>Counted as <strong>Present</strong> in attendance report</li>
                    <li>No salary deduction in payroll</li>
                    <li>Shown distinctly in monthly attendance grid</li>
                    <li>Deleting OD also removes the attendance record</li>
                </ul>
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
$(function(){
    $('#selectAllEmp').on('click', function(e){
        e.preventDefault();
        $('.emp-check').prop('checked', true);
    });
    $('#clearAllEmp').on('click', function(e){
        e.preventDefault();
        $('.emp-check').prop('checked', false);
    });
});
</script>
@endpush
