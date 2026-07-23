@extends('layouts.app')
@section('title','Attendance — Daily Mark Sheet')
@section('breadcrumb')
<li class="breadcrumb-item active">Attendance</li>
@endsection

@section('content')

{{-- Monthly import result --}}
@if(session('monthly_import_result'))
@php $mr = session('monthly_import_result'); @endphp
<div class="alert alert-success alert-dismissible fade show mb-3">
    <div class="d-flex align-items-center gap-2 mb-1">
        <i class="fa fa-calendar-check fs-5 text-success"></i>
        <strong>Monthly Attendance Import Complete</strong>
        @if($mr['month'])<span class="text-muted small ms-1">— {{ $mr['month'] }}</span>@endif
    </div>
    <div class="d-flex gap-4 flex-wrap">
        <span><i class="fa fa-users text-primary me-1"></i><strong>{{ $mr['employees'] }}</strong> employees processed</span>
        <span><i class="fa fa-plus-circle text-success me-1"></i><strong>{{ $mr['saved'] }}</strong> new records</span>
        <span><i class="fa fa-pen text-primary me-1"></i><strong>{{ $mr['updated'] }}</strong> updated</span>
        <span><i class="fa fa-ban text-warning me-1"></i><strong>{{ $mr['skipped'] }}</strong> employees not matched</span>
    </div>
    @if(!empty($mr['warnings']))
    <details class="mt-2">
        <summary class="text-warning fw-semibold small" style="cursor:pointer">{{ count($mr['warnings']) }} warning(s) — click to expand</summary>
        <ul class="mt-1 mb-0 small">@foreach($mr['warnings'] as $w)<li>{{ $w }}</li>@endforeach</ul>
    </details>
    @endif
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif

{{-- Daily import result --}}
@if(session('import_result'))
@php $r = session('import_result'); @endphp
<div class="alert alert-success alert-dismissible fade show mb-3">
    <div class="d-flex align-items-center gap-2 mb-1">
        <i class="fa fa-file-excel fs-5"></i>
        <strong>Attendance Import Complete</strong>
        <span class="text-muted small ms-1">for {{ \Carbon\Carbon::parse($r['date'])->format('d M Y') }}</span>
    </div>
    <div class="d-flex gap-4">
        <span><i class="fa fa-plus-circle text-success me-1"></i><strong>{{ $r['saved'] }}</strong> new records</span>
        <span><i class="fa fa-pen text-primary me-1"></i><strong>{{ $r['updated'] }}</strong> updated</span>
        <span><i class="fa fa-ban text-danger me-1"></i><strong>{{ $r['skipped'] }}</strong> skipped (emp not found)</span>
    </div>
    @if(!empty($r['warnings']))
    <details class="mt-2">
        <summary class="text-warning fw-semibold small cursor-pointer">{{ count($r['warnings']) }} warning(s) — click to expand</summary>
        <ul class="mt-1 mb-0 small">@foreach($r['warnings'] as $w)<li>{{ $w }}</li>@endforeach</ul>
    </details>
    @endif
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif

@if(session('import_error'))
<div class="alert alert-danger alert-dismissible fade show mb-3">
    <i class="fa fa-exclamation-triangle me-2"></i>{{ session('import_error') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif

<div class="card page-card">
    <div class="card-header bg-white py-3 d-flex align-items-center justify-content-between flex-wrap gap-2">
        <h5 class="mb-0 fw-semibold"><i class="fa fa-calendar-check me-2 text-primary"></i>Daily Attendance Mark Sheet</h5>
        <div class="d-flex gap-2">
            <button class="btn btn-sm btn-outline-success" data-bs-toggle="modal" data-bs-target="#importModal">
                <i class="fa fa-file-excel me-1"></i>Daily Import
            </button>
            <button class="btn btn-sm btn-success" data-bs-toggle="modal" data-bs-target="#monthlyImportModal">
                <i class="fa fa-calendar-arrow-up me-1"></i>Monthly Import
            </button>
            <a href="{{ route('attendance.report') }}" class="btn btn-sm btn-outline-secondary">
                <i class="fa fa-chart-bar me-1"></i>Monthly Report
            </a>
        </div>
    </div>

    <div class="card-body">
        @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show">{{ session('success') }}<button class="btn-close" data-bs-dismiss="alert"></button></div>
        @endif

        {{-- Date picker --}}
        <form method="GET" class="row g-2 mb-3 align-items-end">
            <div class="col-auto">
                <label class="form-label fw-semibold mb-1">Date</label>
                <input type="date" name="date" class="form-control" value="{{ $date }}" max="{{ today()->toDateString() }}">
            </div>
            <div class="col-auto">
                <button type="submit" class="btn btn-primary"><i class="fa fa-search me-1"></i>Load</button>
            </div>
        </form>

        {{-- Non-working day banner --}}
        @if($isNonWorking && $nonWorkingLabel)
        <div class="alert alert-warning d-flex align-items-center gap-2 mb-3 py-2">
            <i class="fa fa-moon fa-lg text-warning"></i>
            <div>
                <strong>{{ \Carbon\Carbon::parse($date)->format('l, d M Y') }}</strong> —
                {{ $nonWorkingLabel }}
                <span class="text-muted small ms-2">Attendance can still be saved if needed.</span>
            </div>
        </div>
        @endif

        <form action="{{ route('attendance.store') }}" method="POST">
            @csrf
            <input type="hidden" name="date" value="{{ $date }}">

            @if($employees->isEmpty())
                <p class="text-muted text-center py-4">No active employees found.</p>
            @else
            <div class="table-responsive">
                <table class="table table-bordered table-hover align-middle">
                    <thead class="table-dark">
                        <tr>
                            <th style="width:40px">#</th>
                            <th>Employee</th>
                            <th>Department</th>
                            <th style="min-width:160px">Status</th>
                            <th style="min-width:110px">Check In</th>
                            <th style="min-width:110px">Check Out</th>
                            <th style="min-width:90px">OT Hrs</th>
                            <th>Remarks</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($employees as $i => $emp)
                        @php
                            $rec           = $emp->attendance->first();
                            $currentStatus = $attendanceMap[$emp->id] ?? 'absent';
                        @endphp
                        <tr data-emp-id="{{ $emp->id }}"
                            data-ot-enabled="{{ $emp->ot_enabled ? '1' : '0' }}"
                            data-basic="{{ round($emp->fixed_salary * 0.40, 4) }}">
                            <td class="text-muted">{{ $i + 1 }}</td>
                            <td>
                                <strong>{{ $emp->full_name }}</strong><br>
                                <small class="text-muted">{{ $emp->employee_code }}</small>
                                @if($emp->ot_enabled)
                                    <br><span class="badge mt-1" style="background:#fef9c3;color:#92400e;font-size:.65rem;border:1px solid #fde68a"><i class="fa fa-clock me-1"></i>Auto OT</span>
                                @endif
                            </td>
                            <td>{{ $emp->department?->name ?? '-' }}</td>
                            <td>
                                <select name="attendance[{{ $emp->id }}]" class="form-select form-select-sm status-select">
                                    @foreach(\App\Models\Attendance::$statuses as $val => $label)
                                        <option value="{{ $val }}" {{ $currentStatus === $val ? 'selected' : '' }}>{{ $label }}</option>
                                    @endforeach
                                </select>
                            </td>
                            <td>
                                <input type="time" name="check_in[{{ $emp->id }}]" class="form-control form-control-sm"
                                    value="{{ $rec?->check_in ?? '' }}">
                            </td>
                            <td>
                                <input type="time" name="check_out[{{ $emp->id }}]"
                                    class="form-control form-control-sm checkout-input"
                                    value="{{ $rec?->check_out ?? '' }}">
                            </td>
                            <td>
                                @if($emp->ot_enabled)
                                    {{-- Auto OT: hidden input carries the calculated hours on submit --}}
                                    <input type="hidden" name="ot_hours[{{ $emp->id }}]" class="ot-hours-input" value="{{ $rec?->ot_hours ?? '' }}">
                                    <span class="ot-hours-display fw-semibold" style="font-size:.85rem;color:#16a34a">
                                        {{ $rec?->ot_hours ? number_format($rec->ot_hours, 2).' hrs' : '—' }}
                                    </span>
                                @else
                                    <input type="number" name="ot_hours[{{ $emp->id }}]"
                                        class="form-control form-control-sm"
                                        min="0" max="24" step="0.01" placeholder="0"
                                        value="{{ $rec?->ot_hours ?? '' }}">
                                @endif
                            </td>
                            <td>
                                <input type="text" name="remarks[{{ $emp->id }}]" class="form-control form-control-sm"
                                    placeholder="Optional"
                                    value="{{ $rec?->remarks ?? '' }}">
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="mt-3 d-flex gap-2">
                <button type="submit" class="btn btn-success"><i class="fa fa-save me-1"></i>Save Attendance</button>
                <button type="button" id="markAllPresent" class="btn btn-outline-primary">Mark All Present</button>
                <button type="button" id="markAllAbsent" class="btn btn-outline-danger">Mark All Absent</button>
            </div>
            @endif
        </form>
    </div>
</div>

{{-- Import Modal --}}
<div class="modal fade" id="importModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-semibold">
                    <i class="fa fa-file-excel me-2 text-success"></i>Import Attendance from Machine Report
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                {{-- How it works --}}
                <div class="alert alert-light border mb-3">
                    <div class="fw-semibold mb-2"><i class="fa fa-info-circle text-primary me-1"></i>How the import works:</div>
                    <ul class="small mb-0">
                        <li>Upload the <strong>Daily IN/OUT Report</strong> XLS file exported from your biometric attendance machine.</li>
                        <li>The system reads the <strong>date</strong> from the file header automatically.</li>
                        <li>Each employee is matched by <strong>Employee Code</strong> — make sure codes in the machine match the codes in this system.</li>
                        <li>If an attendance record already exists for that employee+date, it is <strong>overwritten</strong> with the imported data.</li>
                    </ul>
                </div>

                {{-- Status legend --}}
                <div class="mb-3">
                    <div class="fw-semibold small text-muted mb-2">How status is determined from the file:</div>
                    <div class="row g-2 small">
                        <div class="col-md-6">
                            <div class="d-flex align-items-center gap-2 mb-1">
                                <span class="badge bg-danger" style="min-width:80px">Absent</span>
                                <span>No check-in punch recorded (<code>--:--</code>)</span>
                            </div>
                            <div class="d-flex align-items-center gap-2 mb-1">
                                <span class="badge bg-success" style="min-width:80px">Present</span>
                                <span>Check-in recorded, worked ≥ 4 hours</span>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="d-flex align-items-center gap-2 mb-1">
                                <span class="badge bg-warning text-dark" style="min-width:80px">Half Day</span>
                                <span>Check-in recorded, worked &lt; 4 hours</span>
                            </div>
                            <div class="d-flex align-items-center gap-2 mb-1">
                                <span class="badge bg-info" style="min-width:80px">Late</span>
                                <span>Check-in after 09:30, worked ≥ 4 hours</span>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Upload form --}}
                <form action="{{ route('attendance.import') }}" method="POST" enctype="multipart/form-data" id="attendImportForm">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Select Attendance Report File <span class="text-danger">*</span></label>
                        <input type="file" name="file" id="attendFile" class="form-control" accept=".xlsx,.xls" required>
                        <div class="form-text">Accepted: .xlsx, .xls — Max 10 MB</div>
                    </div>
                    <div id="attendPreview" class="d-none alert alert-secondary small py-2">
                        <i class="fa fa-file-excel text-success me-1"></i><span id="attendFileName"></span>
                    </div>
                </form>
            </div>
            <div class="modal-footer border-0 pt-0">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" form="attendImportForm" class="btn btn-success" id="btnAttendImport">
                    <i class="fa fa-upload me-1"></i>Import Attendance
                </button>
            </div>
        </div>
    </div>
</div>
{{-- Monthly Import Modal --}}
<div class="modal fade" id="monthlyImportModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-semibold">
                    <i class="fa fa-calendar-check me-2 text-success"></i>Monthly Attendance Import
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-light border mb-3">
                    <div class="fw-semibold mb-2"><i class="fa fa-info-circle text-primary me-1"></i>How monthly import works:</div>
                    <ul class="small mb-0">
                        <li>Upload the <strong>Monthly IN/OUT Report</strong> XLS from your biometric machine (e.g. <code>monthinout…xls</code>).</li>
                        <li>The file may contain <strong>multiple employees</strong> — each is processed automatically.</li>
                        <li>The <strong>report month</strong> is read from the file header — no manual selection needed.</li>
                        <li>Employees are matched by <strong>Employee Code</strong>. Codes like <code>0021</code> and <code>21</code> both work.</li>
                        <li><strong>Weekly-off days</strong> (Shift = X) are skipped — only working days are imported.</li>
                        <li>OT hours from the <strong>OT column</strong> are imported automatically.</li>
                        <li>If a record already exists for an employee + date, it is <strong>overwritten</strong>.</li>
                    </ul>
                </div>

                <div class="mb-3">
                    <div class="fw-semibold small text-muted mb-2">Status is determined as:</div>
                    <div class="row g-2 small">
                        <div class="col-md-6">
                            <div class="d-flex align-items-center gap-2 mb-1">
                                <span class="badge bg-secondary" style="min-width:90px">Weekly Off</span>
                                <span>Shift = <code>X</code> — row skipped</span>
                            </div>
                            <div class="d-flex align-items-center gap-2 mb-1">
                                <span class="badge bg-danger" style="min-width:90px">Absent</span>
                                <span>Shift = <code>G</code>, IN = <code>--:--</code></span>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="d-flex align-items-center gap-2 mb-1">
                                <span class="badge bg-warning text-dark" style="min-width:90px">Half Day</span>
                                <span>Has check-in, worked &lt; 4 hrs</span>
                            </div>
                            <div class="d-flex align-items-center gap-2 mb-1">
                                <span class="badge bg-info" style="min-width:90px">Late</span>
                                <span>Check-in after 09:30, worked ≥ 4 hrs</span>
                            </div>
                            <div class="d-flex align-items-center gap-2 mb-1">
                                <span class="badge bg-success" style="min-width:90px">Present</span>
                                <span>Check-in ≤ 09:30, worked ≥ 4 hrs</span>
                            </div>
                        </div>
                    </div>
                </div>

                <form action="{{ route('attendance.import-monthly') }}" method="POST"
                      enctype="multipart/form-data" id="monthlyImportForm">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Select Monthly Report File <span class="text-danger">*</span></label>
                        <input type="file" name="file" id="monthlyFile" class="form-control"
                               accept=".xlsx,.xls" required>
                        <div class="form-text">Accepted: .xlsx, .xls — Max 20 MB</div>
                    </div>
                    <div id="monthlyPreview" class="d-none alert alert-secondary small py-2">
                        <i class="fa fa-file-excel text-success me-1"></i><span id="monthlyFileName"></span>
                    </div>
                </form>
            </div>
            <div class="modal-footer border-0 pt-0">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" form="monthlyImportForm" class="btn btn-success" id="btnMonthlyImport">
                    <i class="fa fa-upload me-1"></i>Import Monthly Attendance
                </button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(function(){
    $('#markAllPresent').on('click', function(){ $('select.status-select').val('present'); });
    $('#markAllAbsent').on('click', function(){ $('select.status-select').val('absent'); });

    // Auto OT calculation for OT-enabled employees
    // Trigger  : checkout >= 20:30 (8:30 PM)
    // OT start : 18:15 (6:15 PM) — hours counted from this baseline
    // Formula  : daily_rate  = Basic / days_in_month
    //            hourly_rate = daily_rate / 8
    //            ot_rate     = hourly_rate × 2
    //            OT amount   = ot_rate × OT hours  (2 decimal places)
    var OT_TRIGGER_MINS  = 20 * 60 + 30;   // 1230 — must reach 8:30 PM (trigger)
    var OT_BASELINE_MINS = 18 * 60 + 15;   // 1095 — count OT hours from 6:15 PM

    function calcAutoOT($tr, val) {
        var $hoursInput = $tr.find('.ot-hours-input');
        var $hoursDisp  = $tr.find('.ot-hours-display');

        if (!val) {
            $hoursInput.val('');
            $hoursDisp.text('—');
            return;
        }

        var parts   = val.split(':');
        var outMins = parseInt(parts[0], 10) * 60 + parseInt(parts[1], 10);

        if (outMins < OT_TRIGGER_MINS) {
            $hoursInput.val('');
            $hoursDisp.text('—');
            return;
        }

        // OT hours from 6:15 PM baseline
        var otMins  = outMins - OT_BASELINE_MINS;
        var otHours = Math.round(otMins / 60 * 100) / 100;

        $hoursInput.val(otHours.toFixed(2));
        $hoursDisp.text(otHours.toFixed(2) + ' hrs');
    }

    $(document).on('change', '.checkout-input', function() {
        var $tr = $(this).closest('tr');
        if (!parseInt($tr.data('ot-enabled'))) return;
        calcAutoOT($tr, $(this).val());
    });

    $('#attendFile').on('change', function(){
        var f = this.files[0];
        if (f) {
            $('#attendFileName').text(f.name + ' (' + (f.size/1024).toFixed(1) + ' KB)');
            $('#attendPreview').removeClass('d-none');
        }
    });

    $('#attendImportForm').on('submit', function(){
        $('#btnAttendImport').prop('disabled', true)
            .html('<span class="spinner-border spinner-border-sm me-1"></span>Importing…');
    });

    $('#monthlyFile').on('change', function(){
        var f = this.files[0];
        if (f) {
            $('#monthlyFileName').text(f.name + ' (' + (f.size/1024).toFixed(1) + ' KB)');
            $('#monthlyPreview').removeClass('d-none');
        }
    });

    $('#monthlyImportForm').on('submit', function(){
        $('#btnMonthlyImport').prop('disabled', true)
            .html('<span class="spinner-border spinner-border-sm me-1"></span>Importing… this may take a moment');
    });
});
</script>
@endpush
