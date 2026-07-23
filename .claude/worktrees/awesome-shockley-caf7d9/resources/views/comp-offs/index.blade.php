@extends('layouts.app')
@section('title', 'Comp Off Management')
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('holidays.index') }}" class="text-decoration-none">Holidays</a></li>
    <li class="breadcrumb-item active">Comp Offs</li>
@endsection

@section('content')


<div class="card page-card">
    <div class="card-header bg-white py-3 d-flex align-items-center justify-content-between flex-wrap gap-2">
        <div class="d-flex align-items-center gap-2">
            <a href="{{ route('holidays.index') }}" class="btn btn-sm btn-outline-secondary">
                <i class="fa fa-arrow-left"></i>
            </a>
            <h5 class="mb-0 fw-semibold">
                <i class="fa fa-calendar-plus me-2" style="color:#6f42c1"></i>Comp Off Management
            </h5>
        </div>
        <form method="GET" class="d-flex gap-2 align-items-center">
            <label class="form-label mb-0 text-muted small">Year:</label>
            <select name="year" class="form-select form-select-sm" style="width:90px" onchange="this.form.submit()">
                @for($y = now()->year + 1; $y >= now()->year - 2; $y--)
                    <option value="{{ $y }}" {{ $y == $year ? 'selected' : '' }}>{{ $y }}</option>
                @endfor
            </select>
        </form>
    </div>

    <div class="card-body">

        <div class="alert alert-info border-info d-flex align-items-start gap-2 py-2 mb-4 small">
            <i class="fa fa-circle-info mt-1 text-info flex-shrink-0"></i>
            <div>
                Comp offs are auto-granted to all employees when you mark a holiday as a <strong>Working Day</strong>
                on the <a href="{{ route('holidays.index') }}">Holidays</a> page.
                Once employees take their comp off, click <strong>"Set Availed Date"</strong> to record it for all.
                Then mark attendance as <span class="badge" style="background:#6f42c1;font-size:.75rem">CO</span> on that date.
            </div>
        </div>

        @if($workingHolidays->isEmpty())
            <div class="text-center py-5 text-muted">
                <i class="fa fa-calendar-xmark fa-3x d-block mb-3 opacity-25"></i>
                <p class="mb-1 fw-semibold">No working holidays in {{ $year }}</p>
                <p class="small">Go to <a href="{{ route('holidays.index') }}">Holidays</a> and toggle a holiday as a working day.</p>
            </div>
        @else
            @foreach($workingHolidays as $wh)
            @php
                $dateStr    = $wh->date->toDateString();
                $summary    = $summaryMap[$dateStr] ?? [];
                $pendingCnt = $summary['pending'] ?? 0;
                $availedCnt = $summary['availed'] ?? 0;
                $grantedCnt = $pendingCnt + $availedCnt;
                $notGranted = $totalActive - $grantedCnt;
                $allAvailed = $availedCnt >= $totalActive && $totalActive > 0;
            @endphp

            <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 px-3 py-3 mb-3 rounded border"
                 style="background:#fffbeb;border-color:#fde68a!important">

                {{-- Left: date info + badges --}}
                <div class="d-flex align-items-center gap-3 flex-wrap">
                    <div>
                        <i class="fa fa-briefcase me-1" style="color:#b45309"></i>
                        <strong style="color:#78350f">{{ $wh->name }}</strong>
                        <span class="text-muted ms-2" style="font-size:.85rem">
                            {{ $wh->date->format('d M Y') }} &mdash; {{ $wh->date->format('l') }}
                        </span>
                    </div>
                    <div class="d-flex gap-2 flex-wrap">
                        @if($notGranted > 0)
                            <span class="badge bg-secondary">{{ $notGranted }} not granted</span>
                        @endif
                        @if($pendingCnt > 0)
                            <span class="badge bg-warning text-dark">{{ $pendingCnt }} pending</span>
                        @endif
                        @if($availedCnt > 0)
                            <span class="badge bg-success">{{ $availedCnt }} availed</span>
                        @endif
                        @if($grantedCnt === 0)
                            <span class="badge bg-light text-dark border">No comp offs granted yet</span>
                        @endif
                    </div>
                </div>

                {{-- Right: action buttons --}}
                <div class="d-flex gap-2 align-items-center">

                    {{-- Grant to all (if any missing) --}}
                    @if($notGranted > 0)
                    <form action="{{ route('comp-offs.bulk') }}" method="POST">
                        @csrf
                        <input type="hidden" name="holiday_date" value="{{ $dateStr }}">
                        <input type="hidden" name="holiday_name" value="{{ $wh->name }}">
                        <button type="submit" class="btn btn-sm btn-outline-success">
                            <i class="fa fa-users me-1"></i>Grant to All
                        </button>
                    </form>
                    @endif

                    {{-- Set availed date for all pending --}}
                    @if($pendingCnt > 0 && !$allAvailed)
                    <button type="button" class="btn btn-sm btn-success"
                            data-bs-toggle="modal"
                            data-bs-target="#availModal_{{ $wh->id }}">
                        <i class="fa fa-calendar-check me-1"></i>Set Availed Date
                    </button>
                    @endif

                    {{-- All availed indicator --}}
                    @if($allAvailed)
                    <span class="badge bg-success py-2 px-3" style="font-size:.8rem">
                        <i class="fa fa-check-circle me-1"></i>All Availed
                    </span>
                    @endif

                    {{-- Remove comp off for this date --}}
                    @if($grantedCnt > 0)
                    <button type="button" class="btn btn-sm btn-outline-danger"
                            data-bs-toggle="modal"
                            data-bs-target="#removeModal_{{ $wh->id }}">
                        <i class="fa fa-trash me-1"></i>Remove Comp Off
                    </button>
                    @endif

                </div>
            </div>

            {{-- Avail date modal --}}
            @if($pendingCnt > 0)
            <div class="modal fade" id="availModal_{{ $wh->id }}" tabindex="-1">
                <div class="modal-dialog modal-sm">
                    <div class="modal-content">
                        <div class="modal-header border-0 pb-0">
                            <h6 class="modal-title fw-semibold">
                                <i class="fa fa-calendar-check me-1 text-success"></i>Set Comp Off Availed Date
                            </h6>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <form action="{{ route('comp-offs.bulk-avail') }}" method="POST">
                            @csrf
                            <input type="hidden" name="holiday_date" value="{{ $dateStr }}">
                            <div class="modal-body">
                                <p class="small text-muted mb-3">
                                    Select the date on which employees availed their comp off for
                                    <strong>{{ $wh->name }}</strong> ({{ $wh->date->format('d M Y') }}).
                                    This will update all <strong>{{ $pendingCnt }} pending</strong> comp offs at once.
                                </p>
                                <label class="form-label fw-semibold small">
                                    Availed On <span class="text-danger">*</span>
                                </label>
                                <input type="date" name="availed_date"
                                       class="form-control form-control-sm"
                                       value="{{ today()->toDateString() }}" required>
                                <div class="form-text mt-1">
                                    Also mark attendance as <span class="badge" style="background:#6f42c1;font-size:.7rem">CO</span>
                                    on this date for each employee.
                                </div>
                            </div>
                            <div class="modal-footer border-0 pt-0">
                                <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                <button type="submit" class="btn btn-sm btn-success">
                                    <i class="fa fa-check me-1"></i>Confirm for All
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            @endif

            {{-- Remove comp off confirmation modal --}}
            @if($grantedCnt > 0)
            <div class="modal fade" id="removeModal_{{ $wh->id }}" tabindex="-1">
                <div class="modal-dialog modal-sm">
                    <div class="modal-content">
                        <div class="modal-header border-0 pb-0">
                            <h6 class="modal-title fw-semibold text-danger">
                                <i class="fa fa-triangle-exclamation me-1"></i>Remove Comp Off
                            </h6>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <form action="{{ route('comp-offs.bulk-remove') }}" method="POST">
                            @csrf
                            <input type="hidden" name="holiday_date" value="{{ $dateStr }}">
                            <div class="modal-body">
                                <p class="small text-muted mb-2">
                                    This will remove all <strong>{{ $grantedCnt }} comp off record(s)</strong>
                                    for <strong>{{ $wh->name }}</strong> ({{ $wh->date->format('d M Y') }}).
                                </p>
                                @if($availedCnt > 0)
                                <div class="alert alert-warning py-2 small mb-0">
                                    <i class="fa fa-circle-exclamation me-1"></i>
                                    <strong>{{ $availedCnt }} availed</strong> attendance record(s) auto-created by this comp off will also be removed from the attendance report.
                                </div>
                                @endif
                            </div>
                            <div class="modal-footer border-0 pt-0">
                                <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                <button type="submit" class="btn btn-sm btn-danger">
                                    <i class="fa fa-trash me-1"></i>Yes, Remove All
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            @endif

            @endforeach
        @endif

    </div>
</div>

@endsection
