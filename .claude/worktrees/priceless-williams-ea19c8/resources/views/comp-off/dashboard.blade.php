@extends('layouts.app')
@section('title', 'Comp Off Dashboard')
@section('breadcrumb')
    <li class="breadcrumb-item active">Comp Off Dashboard</li>
@endsection

@section('content')

{{-- ── Stat Cards ──────────────────────────────────────────────────────────── --}}
<div class="row g-3 mb-4">
    <div class="col-sm-6 col-xl-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="rounded-circle bg-primary bg-opacity-10 p-3">
                    <i class="fa fa-circle-plus fa-lg text-primary"></i>
                </div>
                <div>
                    <div class="fs-4 fw-bold">{{ $totalCredited }}</div>
                    <div class="text-muted small">Total Credits Earned</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="rounded-circle bg-success bg-opacity-10 p-3">
                    <i class="fa fa-calendar-check fa-lg text-success"></i>
                </div>
                <div>
                    <div class="fs-4 fw-bold">{{ $empWithBalance }}</div>
                    <div class="text-muted small">Employees with Balance</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="rounded-circle bg-warning bg-opacity-10 p-3">
                    <i class="fa fa-arrow-right-from-bracket fa-lg text-warning"></i>
                </div>
                <div>
                    <div class="fs-4 fw-bold">{{ $totalUsed }}</div>
                    <div class="text-muted small">Total Days Used</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="rounded-circle bg-info bg-opacity-10 p-3">
                    <i class="fa fa-scale-balanced fa-lg text-info"></i>
                </div>
                <div>
                    <div class="fs-4 fw-bold">{{ $totalBalance }}</div>
                    <div class="text-muted small">Total Balance Remaining</div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row g-4">

    {{-- ── Chart ──────────────────────────────────────────────────────────── --}}
    <div class="col-lg-7">
        <div class="card page-card h-100">
            <div class="card-header bg-white py-3">
                <h6 class="mb-0 fw-semibold"><i class="fa fa-chart-bar me-2 text-primary"></i>Credits vs Usage (Last 6 Months)</h6>
            </div>
            <div class="card-body">
                <canvas id="compOffChart" height="200"></canvas>
            </div>
        </div>
    </div>

    {{-- ── Quick Actions ────────────────────────────────────────────────────── --}}
    <div class="col-lg-5">
        <div class="card page-card h-100">
            <div class="card-header bg-white py-3">
                <h6 class="mb-0 fw-semibold"><i class="fa fa-bolt me-2 text-warning"></i>Quick Actions</h6>
            </div>
            <div class="card-body d-flex flex-column gap-3">
                <a href="{{ route('comp-off.balance') }}" class="btn btn-outline-primary w-100 text-start">
                    <i class="fa fa-list-check me-2"></i>View All Employee Balances
                </a>
                <a href="{{ route('comp-off.credits') }}" class="btn btn-outline-success w-100 text-start">
                    <i class="fa fa-calendar-days me-2"></i>Holiday Worked Report
                </a>
                @if($compOffType)
                <a href="{{ route('leave-requests.create') }}?leave_type={{ $compOffType->id }}"
                   class="btn btn-outline-warning w-100 text-start">
                    <i class="fa fa-plus me-2"></i>New Comp Off Leave Request
                </a>
                @endif
                <hr class="my-1">
                {{-- Manual Sync --}}
                <div>
                    <p class="small text-muted mb-2">
                        <i class="fa fa-rotate me-1"></i>
                        <strong>Backfill credits</strong> — scan past attendance and generate any missing comp off credits.
                    </p>
                    <form action="{{ route('comp-off.sync') }}" method="POST" class="row g-2">
                        @csrf
                        <div class="col-5">
                            <input type="date" name="from" class="form-control form-control-sm"
                                   value="{{ now()->startOfMonth()->toDateString() }}" required>
                        </div>
                        <div class="col-5">
                            <input type="date" name="to" class="form-control form-control-sm"
                                   value="{{ now()->toDateString() }}" required>
                        </div>
                        <div class="col-2">
                            <button type="submit" class="btn btn-sm btn-secondary w-100" title="Sync">
                                <i class="fa fa-rotate"></i>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    {{-- ── Recent Credits ────────────────────────────────────────────────────── --}}
    <div class="col-lg-7">
        <div class="card page-card">
            <div class="card-header bg-white py-3 d-flex align-items-center justify-content-between">
                <h6 class="mb-0 fw-semibold"><i class="fa fa-circle-plus me-2 text-success"></i>Recent Comp Off Credits</h6>
                <a href="{{ route('comp-off.credits') }}" class="btn btn-sm btn-outline-secondary">View All</a>
            </div>
            <div class="table-responsive">
                <table class="table table-hover table-sm mb-0 align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>Employee</th>
                            <th>Date Worked</th>
                            <th>Day Type</th>
                            <th>Holiday / Reason</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($recentCredits as $credit)
                        <tr>
                            <td>
                                <strong>{{ $credit->employee?->full_name ?? '—' }}</strong>
                                <br><small class="text-muted">{{ $credit->employee?->employee_code }}</small>
                            </td>
                            <td>{{ $credit->work_date->format('d M Y') }}</td>
                            <td>
                                <span class="badge bg-{{ $credit->day_type_color }}">
                                    {{ $credit->day_type_label }}
                                </span>
                            </td>
                            <td class="small text-muted">{{ $credit->holiday_name ?? '—' }}</td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="4" class="text-center text-muted py-4">
                                No comp off credits yet. Credits are auto-generated when employees work on holidays or weekly offs.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- ── Pending Requests ─────────────────────────────────────────────────── --}}
    <div class="col-lg-5">
        <div class="card page-card">
            <div class="card-header bg-white py-3 d-flex align-items-center justify-content-between">
                <h6 class="mb-0 fw-semibold"><i class="fa fa-hourglass-half me-2 text-warning"></i>Pending Comp Off Requests</h6>
                <a href="{{ route('leave-requests.index') }}" class="btn btn-sm btn-outline-secondary">View All</a>
            </div>
            <div class="table-responsive">
                <table class="table table-hover table-sm mb-0 align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>Employee</th>
                            <th>Days</th>
                            <th>Submitted</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($pendingRequests as $req)
                        <tr>
                            <td>
                                <strong class="small">{{ $req->employee?->full_name ?? '—' }}</strong>
                            </td>
                            <td><span class="badge bg-secondary">{{ $req->days_requested }}d</span></td>
                            <td class="small text-muted">{{ $req->created_at->format('d M') }}</td>
                            <td>
                                <a href="{{ route('leave-requests.show', $req) }}"
                                   class="btn btn-xs btn-outline-primary py-0 px-2 small">Review</a>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="4" class="text-center text-muted py-3 small">No pending requests.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

</div>


@endsection

@push('scripts')
<script>
new Chart(document.getElementById('compOffChart'), {
    type: 'bar',
    data: {
        labels: @json($chartLabels),
        datasets: [
            {
                label: 'Credits Earned',
                data: @json($chartCredited),
                backgroundColor: 'rgba(59,130,246,0.7)',
                borderRadius: 4,
            },
            {
                label: 'Days Used',
                data: @json($chartUsed),
                backgroundColor: 'rgba(249,115,22,0.7)',
                borderRadius: 4,
            }
        ]
    },
    options: {
        responsive: true,
        plugins: { legend: { position: 'top' } },
        scales: {
            y: { beginAtZero: true, ticks: { stepSize: 1 } }
        }
    }
});
</script>
@endpush
