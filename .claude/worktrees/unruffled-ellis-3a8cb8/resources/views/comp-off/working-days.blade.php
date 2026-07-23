@extends('layouts.app')
@section('title', 'Company Working Days')
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('comp-off.dashboard') }}" class="text-decoration-none">Comp Off</a></li>
    <li class="breadcrumb-item active">Company Working Days</li>
@endsection

@section('content')

<div class="row g-4">

    {{-- ── Declare New Working Day ──────────────────────────────────────────── --}}
    <div class="col-lg-4">
        <div class="card page-card border-warning">
            <div class="card-header bg-warning bg-opacity-10 py-3 border-warning">
                <h6 class="mb-0 fw-semibold">
                    <i class="fa fa-plus-circle me-2 text-warning"></i>Declare Company Working Day
                </h6>
            </div>
            <div class="card-body">
                <p class="small text-muted mb-3">
                    When the company works on a <strong>public holiday</strong>, <strong>Sunday</strong>, or
                    <strong>1st/3rd Saturday</strong>, declare it here. Employees who attend on this day will
                    automatically earn 1 Comp Off credit.
                </p>

                @if($errors->any())
                <div class="alert alert-danger py-2 small">
                    @foreach($errors->all() as $e)<div>{{ $e }}</div>@endforeach
                </div>
                @endif

                <form action="{{ route('comp-off.working-days.declare') }}" method="POST">
                    @csrf

                    <div class="mb-3">
                        <label class="form-label fw-semibold small">Date <span class="text-danger">*</span></label>
                        <input type="date" name="work_date" class="form-control @error('work_date') is-invalid @enderror"
                               value="{{ old('work_date') }}" required>
                        <div class="form-text small">Must be a Sunday, off-Saturday, or public holiday.</div>
                        @error('work_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="mb-4">
                        <label class="form-label fw-semibold small">Reason / Note</label>
                        <textarea name="reason" class="form-control" rows="2"
                                  placeholder="e.g. Project deadline, Client requirement…">{{ old('reason') }}</textarea>
                    </div>

                    <button type="submit" class="btn btn-warning w-100">
                        <i class="fa fa-calendar-plus me-1"></i>Declare Working Day
                    </button>
                </form>

                <hr>

                <div class="alert alert-light border small py-2 mb-0">
                    <i class="fa fa-circle-info text-primary me-1"></i>
                    <strong>After declaring:</strong> Go to
                    <a href="{{ route('attendance.index') }}" class="text-decoration-none">Mark Attendance</a>
                    and select this date to record which employees worked.
                    Comp Off credits are auto-generated for <strong>present</strong> employees.
                </div>
            </div>
        </div>
    </div>

    {{-- ── Declared Working Days List ───────────────────────────────────────── --}}
    <div class="col-lg-8">
        <div class="card page-card">
            <div class="card-header bg-white py-3 d-flex align-items-center justify-content-between flex-wrap gap-2">
                <h6 class="mb-0 fw-semibold">
                    <i class="fa fa-list-check me-2 text-primary"></i>Declared Company Working Days
                </h6>
                {{-- Year filter --}}
                <form method="GET" class="d-flex align-items-center gap-2">
                    <select name="year" class="form-select form-select-sm" style="width:auto" onchange="this.form.submit()">
                        @for($y = now()->year + 1; $y >= now()->year - 2; $y--)
                            <option value="{{ $y }}" {{ $y == $year ? 'selected' : '' }}>{{ $y }}</option>
                        @endfor
                    </select>
                </form>
            </div>

            
            <div class="card-body p-0">
                @if($workingDays->isEmpty())
                <div class="text-center py-5 text-muted">
                    <i class="fa fa-calendar-xmark fa-3x mb-3 d-block opacity-25"></i>
                    No working days declared for {{ $year }}.
                    <br><small>Use the form on the left to declare a holiday working day.</small>
                </div>
                @else
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-dark">
                            <tr>
                                <th>Date</th>
                                <th class="text-center">Day Type</th>
                                <th>Holiday / Reason</th>
                                <th>Admin Note</th>
                                <th>Declared By</th>
                                <th class="text-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($workingDays as $wd)
                            @php
                                $creditsCount = \App\Models\CompOffCredit::where('work_date', $wd->work_date->toDateString())
                                    ->where('status', 'credited')->count();
                            @endphp
                            <tr>
                                <td>
                                    <strong>{{ $wd->work_date->format('d M Y') }}</strong>
                                    <br><small class="text-muted">{{ $wd->work_date->format('l') }}</small>
                                </td>
                                <td class="text-center">
                                    <span class="badge bg-{{ $wd->day_type_color }}">
                                        {{ $wd->day_type_label }}
                                    </span>
                                </td>
                                <td class="small">{{ $wd->holiday_name ?? '—' }}</td>
                                <td class="small text-muted">{{ $wd->reason ?? '—' }}</td>
                                <td class="small text-muted">{{ $wd->declaredBy?->name ?? '—' }}</td>
                                <td class="text-center">
                                    <div class="d-flex gap-1 justify-content-center align-items-center">
                                        {{-- Mark attendance for this day --}}
                                        <a href="{{ route('attendance.index', ['date' => $wd->work_date->toDateString()]) }}"
                                           class="btn btn-sm btn-outline-success" title="Mark Attendance">
                                            <i class="fa fa-user-check"></i>
                                        </a>
                                        {{-- Credits earned --}}
                                        <a href="{{ route('comp-off.credits', ['month' => $wd->work_date->month, 'year' => $wd->work_date->year]) }}"
                                           class="btn btn-sm btn-outline-primary" title="{{ $creditsCount }} credit(s) earned">
                                            <i class="fa fa-circle-plus"></i>
                                            <span class="badge bg-primary ms-1">{{ $creditsCount }}</span>
                                        </a>
                                        {{-- Delete --}}
                                        <form action="{{ route('comp-off.working-days.remove', $wd) }}" method="POST"
                                              class="d-inline wd-delete-form">
                                            @csrf @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-outline-danger" title="Remove declaration">
                                                <i class="fa fa-trash"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                @if($workingDays->hasPages())
                <div class="d-flex justify-content-between align-items-center px-3 py-2">
                    <small class="text-muted">{{ $workingDays->total() }} record(s)</small>
                    {{ $workingDays->links() }}
                </div>
                @endif
                @endif
            </div>
        </div>
    </div>

</div>
@endsection

@push('scripts')
<script>
document.querySelectorAll('.wd-delete-form').forEach(function(form) {
    form.addEventListener('submit', function(e) {
        e.preventDefault();
        Swal.fire({
            title: 'Remove Declaration?',
            text: 'This removes the working day record. Existing comp off credits already earned are NOT affected.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc3545',
            confirmButtonText: 'Yes, remove it',
        }).then(function(result) {
            if (result.isConfirmed) form.submit();
        });
    });
});
</script>
@endpush
