@extends('layouts.app')
@section('title', 'Holiday Worked Report')
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('comp-off.dashboard') }}" class="text-decoration-none">Comp Off</a></li>
    <li class="breadcrumb-item active">Holiday Worked Report</li>
@endsection

@section('content')
<div class="card page-card">

    <div class="card-header bg-white py-3 d-flex align-items-center justify-content-between flex-wrap gap-2">
        <h5 class="mb-0 fw-semibold">
            <i class="fa fa-calendar-days me-2 text-success"></i>Holiday Worked / Comp Off Credits
        </h5>
    </div>

    {{-- Filters --}}
    <div class="card-body border-bottom pb-3">
        <form method="GET" class="row g-2 align-items-end">
            {{-- Month --}}
            <div class="col-6 col-md-2">
                <label class="form-label small fw-semibold mb-1">Month</label>
                <select name="month" class="form-select form-select-sm" onchange="this.form.submit()">
                    @for($m = 1; $m <= 12; $m++)
                        <option value="{{ $m }}" {{ $m == $month ? 'selected' : '' }}>
                            {{ date('F', mktime(0,0,0,$m,1)) }}
                        </option>
                    @endfor
                </select>
            </div>

            {{-- Year --}}
            <div class="col-6 col-md-2">
                <label class="form-label small fw-semibold mb-1">Year</label>
                <select name="year" class="form-select form-select-sm" onchange="this.form.submit()">
                    @for($y = now()->year + 1; $y >= now()->year - 3; $y--)
                        <option value="{{ $y }}" {{ $y == $year ? 'selected' : '' }}>{{ $y }}</option>
                    @endfor
                </select>
            </div>

            {{-- Day Type --}}
            <div class="col-6 col-md-2">
                <label class="form-label small fw-semibold mb-1">Day Type</label>
                <select name="day_type" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="">All Types</option>
                    <option value="sunday"         {{ $dayType === 'sunday'         ? 'selected' : '' }}>Sunday</option>
                    <option value="saturday"       {{ $dayType === 'saturday'       ? 'selected' : '' }}>Saturday Off</option>
                    <option value="public_holiday" {{ $dayType === 'public_holiday' ? 'selected' : '' }}>Public Holiday</option>
                </select>
            </div>

            {{-- Status --}}
            <div class="col-6 col-md-2">
                <label class="form-label small fw-semibold mb-1">Status</label>
                <select name="status" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="credited"  {{ $status === 'credited'  ? 'selected' : '' }}>Credited</option>
                    <option value="cancelled" {{ $status === 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                    <option value="all"       {{ $status === 'all'       ? 'selected' : '' }}>All</option>
                </select>
            </div>

            {{-- Department --}}
            <div class="col-md-2">
                <label class="form-label small fw-semibold mb-1">Department</label>
                <select name="department" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="">All Depts</option>
                    @foreach($departments as $dept)
                        <option value="{{ $dept->id }}" {{ $deptId == $dept->id ? 'selected' : '' }}>
                            {{ $dept->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            {{-- Search --}}
            <div class="col-md-2">
                <label class="form-label small fw-semibold mb-1">Search</label>
                <div class="input-group input-group-sm">
                    <input type="text" name="search" class="form-control"
                           placeholder="Name / code…" value="{{ $search ?? '' }}">
                    <button type="submit" class="btn btn-primary"><i class="fa fa-search"></i></button>
                </div>
            </div>
        </form>
    </div>

    <div class="card-body p-0">

        {{-- Summary badge --}}
        <div class="px-3 py-2 border-bottom bg-light small text-muted">
            <strong>{{ $credits->total() }}</strong> record(s) found for
            <strong>{{ date('F Y', mktime(0,0,0,$month,1,$year)) }}</strong>
        </div>

        @if($credits->isEmpty())
            <div class="text-center py-5 text-muted">
                <i class="fa fa-calendar-xmark fa-3x mb-3 d-block opacity-25"></i>
                No comp off credits found for this period.
            </div>
        @else
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-dark">
                    <tr>
                        <th>#</th>
                        <th>Employee</th>
                        <th>Department</th>
                        <th class="text-center">Date Worked</th>
                        <th class="text-center">Day Type</th>
                        <th>Holiday / Reason</th>
                        <th class="text-center">Credit Status</th>
                    </tr>
                </thead>
                <tbody>
                    @php $i = $credits->firstItem(); @endphp
                    @foreach($credits as $credit)
                    <tr>
                        <td class="text-muted small">{{ $i++ }}</td>
                        <td>
                            <strong>{{ $credit->employee?->full_name ?? '—' }}</strong>
                            <br><small class="text-muted">{{ $credit->employee?->employee_code }}</small>
                        </td>
                        <td class="text-muted small">{{ $credit->employee?->department?->name ?? '—' }}</td>
                        <td class="text-center">
                            {{ $credit->work_date->format('d M Y') }}
                            <br><small class="text-muted">{{ $credit->work_date->format('D') }}</small>
                        </td>
                        <td class="text-center">
                            <span class="badge bg-{{ $credit->day_type_color }}">
                                {{ $credit->day_type_label }}
                            </span>
                        </td>
                        <td class="small">{{ $credit->holiday_name ?? '—' }}</td>
                        <td class="text-center">{!! $credit->status_badge !!}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        @if($credits->hasPages())
        <div class="d-flex justify-content-between align-items-center px-3 py-2 flex-wrap gap-2">
            <small class="text-muted">
                Showing {{ $credits->firstItem() }}–{{ $credits->lastItem() }} of {{ $credits->total() }} records
            </small>
            {{ $credits->links() }}
        </div>
        @endif

        @endif
    </div>
</div>
@endsection
