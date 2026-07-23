@extends('layouts.app')
@section('title', 'Holiday Management')
@section('breadcrumb')
    <li class="breadcrumb-item active">Holidays</li>
@endsection

@section('content')

@if(session('import_error'))
<div class="alert alert-danger alert-dismissible fade show mb-3">
    <i class="fa fa-exclamation-triangle me-2"></i>{{ session('import_error') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif

@php $byMonth = $allDays->groupBy(fn($d) => $d['date']->month); @endphp

<div class="row g-4">

    {{-- Left: Month-wise list --}}
    <div class="col-lg-8">
        <div class="card page-card">
            <div class="card-header bg-white py-3 d-flex align-items-center justify-content-between flex-wrap gap-2">
                <h5 class="mb-0 fw-semibold">
                    <i class="fa fa-calendar-day me-2 text-danger"></i>Non-Working Days — {{ $year }}
                </h5>
                <div class="d-flex gap-2 align-items-center flex-wrap">
                    <form method="GET" class="d-flex gap-2">
                        <select name="year" class="form-select form-select-sm" onchange="this.form.submit()">
                            @for($y = now()->year + 1; $y >= now()->year - 3; $y--)
                                <option value="{{ $y }}" {{ $y == $year ? 'selected' : '' }}>{{ $y }}</option>
                            @endfor
                        </select>
                    </form>
                    <a href="{{ route('comp-offs.index') }}" class="btn btn-sm btn-outline-primary">
                        <i class="fa fa-calendar-plus me-1"></i>Comp Offs
                    </a>
                    <button class="btn btn-sm btn-outline-success" data-bs-toggle="modal" data-bs-target="#importModal">
                        <i class="fa fa-file-excel me-1"></i>Import Excel
                    </button>
                </div>
            </div>

            <div class="card-body">

                @forelse($byMonth as $monthNum => $days)
                <div class="mb-4">
                    {{-- Month header --}}
                    <div class="d-flex align-items-center gap-2 mb-1">
                        <span class="badge bg-dark px-3 py-2" style="font-size:.82rem">
                            <i class="fa fa-calendar me-1"></i>
                            {{ \Carbon\Carbon::create($year, $monthNum, 1)->format('F Y') }}
                        </span>
                        <span class="small text-muted">
                            {{ $days->where('kind','holiday')->count() }} holiday(s) ·
                            {{ $days->where('kind','weekly_sat')->count() + $days->where('kind','sunday')->count() }} weekly offs
                        </span>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-bordered table-sm align-middle mb-0" style="font-size:.86rem">
                            <thead class="table-secondary">
                                <tr>
                                    <th style="width:90px">Date</th>
                                    <th style="width:85px">Day</th>
                                    <th>Name</th>
                                    <th style="width:100px">Type</th>
                                    <th class="text-center" style="width:115px">Working Day</th>
                                    <th class="text-center" style="width:145px">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($days as $day)
                                @php
                                    $isHoliday  = $day['kind'] === 'holiday';
                                    $isSat      = $day['kind'] === 'weekly_sat';
                                    $isSun      = $day['kind'] === 'sunday';
                                    $isAuto     = $isSat || $isSun;
                                    $isLocked   = isset($availedHolidayDates[$day['date']->toDateString()]);
                                    $rowBg      = $day['is_working_day'] ? 'table-success' : ($isAuto ? 'table-light' : '');
                                @endphp
                                <tr class="{{ $rowBg }}">
                                    <td class="fw-semibold">{{ $day['date']->format('d M') }}</td>
                                    <td class="{{ $isAuto ? 'text-muted' : '' }}">{{ $day['date']->format('l') }}</td>

                                    <td>
                                        {{ $day['name'] }}
                                        @if($day['is_working_day'])
                                            <span class="badge bg-success ms-1" style="font-size:.65rem">
                                                <i class="fa fa-briefcase me-1"></i>Working
                                            </span>
                                        @endif
                                    </td>

                                    <td>
                                        @if($isHoliday)
                                            <span class="badge bg-{{ $day['type_color'] ?: 'danger' }}">
                                                {{ $day['type_label'] ?: 'Holiday' }}
                                            </span>
                                        @elseif($isSat)
                                            <span class="badge bg-secondary" style="font-size:.7rem">Weekly Off</span>
                                        @else
                                            <span class="badge bg-light text-dark border" style="font-size:.7rem">Sunday</span>
                                        @endif
                                    </td>

                                    {{-- Working Day toggle — locked once comp offs are availed --}}
                                    <td class="text-center">
                                        @if($isLocked)
                                            <span class="btn btn-sm btn-success disabled" style="font-size:.72rem;opacity:.75"
                                                  title="Locked — comp offs have been availed for this date">
                                                <i class="fa fa-lock me-1"></i>Working
                                            </span>
                                        @elseif($isHoliday && $day['model'])
                                            @if($day['is_working_day'])
                                                {{-- Already working: direct restore toggle --}}
                                                <form action="{{ route('holidays.toggle-working-day', $day['model']) }}" method="POST"
                                                      onsubmit="return confirm('Restore as off day? Existing comp offs will not be removed.')">
                                                    @csrf
                                                    <button type="submit" class="btn btn-sm btn-success" style="font-size:.72rem">
                                                        <i class="fa fa-toggle-on me-1"></i>Working
                                                    </button>
                                                </form>
                                            @else
                                                {{-- Not working: open modal to collect reason + entity --}}
                                                <button type="button"
                                                        class="btn btn-sm btn-outline-secondary btn-mark-working"
                                                        style="font-size:.72rem"
                                                        data-action="{{ route('holidays.toggle-working-day', $day['model']) }}"
                                                        data-date="{{ $day['date']->format('d M Y') }}"
                                                        data-name="{{ $day['name'] }}"
                                                        data-type="holiday">
                                                    <i class="fa fa-toggle-off me-1"></i>Holiday
                                                </button>
                                            @endif
                                        @else
                                            @if($day['is_working_day'])
                                                {{-- Auto row already working: direct restore --}}
                                                <form action="{{ route('holidays.toggle-date-working-day') }}" method="POST"
                                                      onsubmit="return confirm('Restore as off day?')">
                                                    @csrf
                                                    <input type="hidden" name="date" value="{{ $day['date']->toDateString() }}">
                                                    <input type="hidden" name="name" value="{{ $day['name'] }}">
                                                    <button type="submit" class="btn btn-sm btn-success" style="font-size:.72rem">
                                                        <i class="fa fa-toggle-on me-1"></i>Working
                                                    </button>
                                                </form>
                                            @else
                                                {{-- Auto row: open modal --}}
                                                <button type="button"
                                                        class="btn btn-sm btn-outline-secondary btn-mark-working"
                                                        style="font-size:.72rem"
                                                        data-action="{{ route('holidays.toggle-date-working-day') }}"
                                                        data-date="{{ $day['date']->format('d M Y') }}"
                                                        data-name="{{ $day['name'] }}"
                                                        data-type="date"
                                                        data-date-val="{{ $day['date']->toDateString() }}">
                                                    <i class="fa fa-toggle-off me-1"></i>Off
                                                </button>
                                            @endif
                                        @endif
                                    </td>

                                    {{-- Actions --}}
                                    <td class="text-center">
                                        @if($isLocked)
                                            <span class="badge py-2 px-2" style="background:#6f42c1;font-size:.7rem"
                                                  title="Comp offs availed for this date">
                                                <i class="fa fa-calendar-check me-1"></i>CO Availed
                                            </span>
                                        @elseif($isHoliday && $day['model'])
                                            <div class="d-flex gap-1 justify-content-center flex-wrap">
                                                @if($day['is_working_day'])
                                                    {{-- Download Circular --}}
                                                    <a href="{{ route('holidays.circular', $day['model']) }}"
                                                       class="btn btn-sm btn-outline-dark" style="font-size:.72rem"
                                                       title="Download Circular PDF">
                                                        <i class="fa fa-file-pdf me-1"></i>Circular
                                                    </a>
                                                    {{-- Comp Off All --}}
                                                    <form action="{{ route('comp-offs.bulk') }}" method="POST"
                                                          onsubmit="return confirm('Grant comp off to ALL active employees for \'{{ addslashes($day['model']->name) }}\'?')">
                                                        @csrf
                                                        <input type="hidden" name="holiday_date" value="{{ $day['date']->toDateString() }}">
                                                        <input type="hidden" name="holiday_name"  value="{{ $day['model']->name }}">
                                                        <button type="submit" class="btn btn-sm btn-outline-primary" style="font-size:.72rem" title="Grant comp off to all">
                                                            <i class="fa fa-users me-1"></i>Comp Off All
                                                        </button>
                                                    </form>
                                                @endif
                                                <form action="{{ route('holidays.destroy', $day['model']) }}" method="POST"
                                                      onsubmit="return confirm('Remove \'{{ addslashes($day['model']->name) }}\'?')">
                                                    @csrf @method('DELETE')
                                                    <button type="submit" class="btn btn-sm btn-outline-danger" style="font-size:.72rem" title="Remove">
                                                        <i class="fa fa-trash"></i>
                                                    </button>
                                                </form>
                                            </div>
                                        @elseif($isAuto && $day['model'] && $day['is_working_day'])
                                            <div class="d-flex gap-1 justify-content-center flex-wrap">
                                                {{-- Download Circular for auto row --}}
                                                <a href="{{ route('holidays.circular', $day['model']) }}"
                                                   class="btn btn-sm btn-outline-dark" style="font-size:.72rem"
                                                   title="Download Circular PDF">
                                                    <i class="fa fa-file-pdf me-1"></i>Circular
                                                </a>
                                                <form action="{{ route('comp-offs.bulk') }}" method="POST"
                                                      onsubmit="return confirm('Grant comp off to ALL active employees for \'{{ addslashes($day['name']) }}\'?')">
                                                    @csrf
                                                    <input type="hidden" name="holiday_date" value="{{ $day['date']->toDateString() }}">
                                                    <input type="hidden" name="holiday_name"  value="{{ $day['name'] }}">
                                                    <button type="submit" class="btn btn-sm btn-outline-primary" style="font-size:.72rem" title="Grant comp off to all">
                                                        <i class="fa fa-users me-1"></i>Comp Off All
                                                    </button>
                                                </form>
                                            </div>
                                        @else
                                            <span class="text-muted" style="font-size:.75rem">—</span>
                                        @endif
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
                @empty
                <p class="text-muted text-center py-5">
                    <i class="fa fa-calendar-xmark fa-2x d-block mb-2 opacity-25"></i>No data for {{ $year }}.
                </p>
                @endforelse

                <div class="small text-muted pt-1 border-top mt-1">
                    {{ $holidays->count() }} public holiday(s) ·
                    {{ $allDays->where('kind','weekly_sat')->count() }} Sat offs ·
                    {{ $allDays->where('kind','sunday')->count() }} Sundays ·
                    <strong>{{ $allDays->count() }} total non-working days</strong>
                </div>
            </div>
        </div>
    </div>

    {{-- Right: Add holiday form --}}
    <div class="col-lg-4">
        <div class="card page-card">
            <div class="card-header bg-white py-3">
                <h6 class="mb-0 fw-semibold"><i class="fa fa-plus-circle me-2 text-success"></i>Add Holiday Manually</h6>
            </div>
            <div class="card-body">
                @if($errors->any())
                <div class="alert alert-danger small py-2"><ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>
                @endif
                <form action="{{ route('holidays.store') }}" method="POST">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Date <span class="text-danger">*</span></label>
                        <input type="date" name="date" class="form-control @error('date') is-invalid @enderror"
                               value="{{ old('date') }}" required>
                        @error('date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Holiday Name <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control @error('name') is-invalid @enderror"
                               placeholder="e.g. Republic Day"
                               value="{{ old('name') }}" required>
                        @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="mb-4">
                        <label class="form-label fw-semibold d-flex justify-content-between align-items-center">
                            <span>Type</span>
                            @if(auth()->user()?->role === 'admin')
                            <a href="{{ route('holiday-types.index') }}" class="small text-decoration-none">
                                <i class="fa fa-cog"></i> Manage Types
                            </a>
                            @endif
                        </label>
                        <select name="holiday_type_id" class="form-select">
                            <option value="">— None —</option>
                            @foreach($types as $t)
                                <option value="{{ $t->id }}" {{ old('holiday_type_id') == $t->id ? 'selected' : '' }}>
                                    {{ $t->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <button type="submit" class="btn btn-success w-100">
                        <i class="fa fa-plus me-1"></i>Add Holiday
                    </button>
                </form>
            </div>
        </div>

        {{-- Quick add national holidays --}}
        <div class="card page-card mt-3">
            <div class="card-header bg-white py-3">
                <h6 class="mb-0 fw-semibold"><i class="fa fa-bolt me-2 text-warning"></i>Quick Add — National Holidays {{ $year }}</h6>
            </div>
            <div class="card-body p-2">
                @php
                $national = [
                    ['date' => $year.'-01-26', 'name' => 'Republic Day'],
                    ['date' => $year.'-08-15', 'name' => 'Independence Day'],
                    ['date' => $year.'-10-02', 'name' => 'Gandhi Jayanti'],
                    ['date' => $year.'-12-25', 'name' => 'Christmas'],
                ];
                $existingDates  = $holidays->pluck('date')->map(fn($d) => $d->toDateString())->toArray();
                $nationalTypeId = $types->firstWhere('name', 'National')?->id ?? $types->firstWhere('name', 'Public')?->id;
                @endphp
                @foreach($national as $h)
                    @if(!in_array($h['date'], $existingDates))
                    <form action="{{ route('holidays.store') }}" method="POST" class="d-flex align-items-center gap-2 p-2 border-bottom">
                        @csrf
                        <input type="hidden" name="date" value="{{ $h['date'] }}">
                        <input type="hidden" name="name"  value="{{ $h['name'] }}">
                        <input type="hidden" name="holiday_type_id" value="{{ $nationalTypeId }}">
                        <div class="flex-grow-1 small">
                            <div class="fw-semibold">{{ $h['name'] }}</div>
                            <div class="text-muted">{{ \Carbon\Carbon::parse($h['date'])->format('d M Y') }}</div>
                        </div>
                        <button type="submit" class="btn btn-sm btn-outline-success" title="Add"><i class="fa fa-plus"></i></button>
                    </form>
                    @else
                    <div class="d-flex align-items-center gap-2 p-2 border-bottom text-muted">
                        <div class="flex-grow-1 small">
                            <div class="fw-semibold">{{ $h['name'] }}</div>
                            <div>{{ \Carbon\Carbon::parse($h['date'])->format('d M Y') }}</div>
                        </div>
                        <span class="badge bg-success"><i class="fa fa-check"></i></span>
                    </div>
                    @endif
                @endforeach
            </div>
        </div>
    </div>
</div>

{{-- Import Excel Modal --}}
<div class="modal fade" id="importModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-semibold">
                    <i class="fa fa-file-excel me-2 text-success"></i>Import Holidays from Excel
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-light border mb-3">
                    <div class="fw-semibold mb-2"><i class="fa fa-info-circle text-primary me-1"></i>Excel File Format:</div>
                    <ul class="small mb-2">
                        <li>Column A — <strong>Month</strong> (number: 1–12)</li>
                        <li>Column B — <strong>Date / Day</strong> (number: 1–31)</li>
                        <li>Column C — <strong>Holiday Name</strong> (optional; defaults to "Holiday")</li>
                        <li>First row can be a header — it will be skipped automatically if non-numeric.</li>
                    </ul>
                </div>
                <form action="{{ route('holidays.import') }}" method="POST" enctype="multipart/form-data" id="holidayImportForm">
                    @csrf
                    <div class="row g-3">
                        <div class="col-md-3">
                            <label class="form-label fw-semibold">Year <span class="text-danger">*</span></label>
                            <select name="year" class="form-select">
                                @for($y = now()->year + 1; $y >= now()->year - 2; $y--)
                                    <option value="{{ $y }}" {{ $y == $year ? 'selected' : '' }}>{{ $y }}</option>
                                @endfor
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-semibold">Apply Type</label>
                            <select name="holiday_type_id" class="form-select">
                                <option value="">— Default (Public) —</option>
                                @foreach($types as $t)
                                    <option value="{{ $t->id }}">{{ $t->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Excel File <span class="text-danger">*</span></label>
                            <input type="file" name="file" class="form-control" accept=".xlsx,.xls,.csv" required id="holidayFile">
                            <div class="form-text">Accepted: .xlsx, .xls, .csv — Max 5 MB</div>
                        </div>
                    </div>
                    <div id="holidayPreview" class="d-none alert alert-secondary small py-2 mt-2">
                        <i class="fa fa-file-excel text-success me-1"></i><span id="holidayFileName"></span>
                    </div>
                </form>
            </div>
            <div class="modal-footer border-0 pt-0">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" form="holidayImportForm" class="btn btn-success" id="btnHolidayImport">
                    <i class="fa fa-upload me-1"></i>Import Holidays
                </button>
            </div>
        </div>
    </div>
</div>

{{-- ── Mark as Working Day Modal ──────────────────────────────────────── --}}
<div class="modal fade" id="workingDayModal" tabindex="-1" aria-labelledby="workingDayModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-warning bg-opacity-10 border-bottom">
                <h5 class="modal-title fw-semibold" id="workingDayModalLabel">
                    <i class="fa fa-calendar-check me-2 text-warning"></i>Mark as Working Day
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="workingDayForm" method="POST">
                @csrf
                <input type="hidden" id="wdDateInput"  name="date">
                <input type="hidden" id="wdNameInput"  name="name">
                <div class="modal-body">
                    <p class="text-muted small mb-3">
                        <i class="fa fa-info-circle me-1 text-primary"></i>
                        <strong id="wdDayName"></strong> will be marked as a working day.
                        Comp off will be auto-granted to all active employees.
                        You can download the circular once saved.
                    </p>

                    {{-- Entity --}}
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Company / Entity <span class="text-danger">*</span></label>
                        <select name="entity_id" id="wdEntity" class="form-select" required>
                            <option value="">Select Entity…</option>
                            @foreach($entities as $ent)
                                <option value="{{ $ent->id }}"
                                    {{ $entities->count() === 1 ? 'selected' : '' }}>
                                    {{ $ent->name }}
                                </option>
                            @endforeach
                        </select>
                        <div class="form-text">This entity name will appear on the circular.</div>
                    </div>

                    {{-- Reason --}}
                    <div class="mb-2">
                        <label class="form-label fw-semibold">Reason for Working Day <span class="text-danger">*</span></label>
                        <textarea name="working_day_reason" id="wdReason" rows="3"
                                  class="form-control"
                                  placeholder="e.g. year-end work, client delivery, project deadline…"
                                  required maxlength="500"></textarea>
                        <div class="form-text">This will appear in the body of the circular.</div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-warning">
                        <i class="fa fa-calendar-check me-1"></i>Mark as Working Day
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
$(function(){
    $('#holidayFile').on('change', function(){
        var f = this.files[0];
        if (f) {
            $('#holidayFileName').text(f.name + ' (' + (f.size/1024).toFixed(1) + ' KB)');
            $('#holidayPreview').removeClass('d-none');
        }
    });
    $('#holidayImportForm').on('submit', function(){
        $('#btnHolidayImport').prop('disabled', true)
            .html('<span class="spinner-border spinner-border-sm me-1"></span>Importing…');
    });

    // ── Working Day Modal ────────────────────────────────────────────────────
    document.querySelectorAll('.btn-mark-working').forEach(function(btn){
        btn.addEventListener('click', function(){
            var action  = this.dataset.action;
            var date    = this.dataset.date;
            var name    = this.dataset.name;
            var type    = this.dataset.type;
            var dateVal = this.dataset.dateVal || '';

            // Set form action
            document.getElementById('workingDayForm').action = action;

            // Populate hidden fields (for date-based toggle)
            document.getElementById('wdDateInput').value = dateVal;
            document.getElementById('wdNameInput').value = name;

            // Update modal heading
            document.getElementById('wdDayName').textContent = name + ' (' + date + ')';

            // Clear previous reason
            document.getElementById('wdReason').value = '';

            // Show modal
            var modal = new bootstrap.Modal(document.getElementById('workingDayModal'));
            modal.show();
        });
    });
});
</script>
@endpush
