@extends('layouts.app')

@section('title', 'Dashboard')

@section('breadcrumb')
    <li class="breadcrumb-item active">Dashboard</li>
@endsection

@section('content')

{{-- Stat Cards --}}
<div class="row g-4 mb-4">
    <div class="col-xl-3 col-md-6">
        <div class="card stat-card h-100">
            <div class="card-body d-flex align-items-center justify-content-between">
                <div>
                    <div class="text-muted small fw-semibold text-uppercase mb-1">Total Employees</div>
                    <div class="h3 mb-0 fw-bold">{{ $totalEmployees }}</div>
                </div>
                <div class="stat-icon" style="background:rgba(78,115,223,.15);color:#4e73df">
                    <i class="fa fa-users"></i>
                </div>
            </div>
            <div class="card-footer bg-transparent border-0 pt-0">
                <a href="{{ route('employees.index') }}" class="small text-primary">View all <i class="fa fa-arrow-right ms-1"></i></a>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6">
        <div class="card stat-card h-100">
            <div class="card-body d-flex align-items-center justify-content-between">
                <div>
                    <div class="text-muted small fw-semibold text-uppercase mb-1">Total Departments</div>
                    <div class="h3 mb-0 fw-bold">{{ $totalDepartments }}</div>
                </div>
                <div class="stat-icon" style="background:rgba(28,200,138,.15);color:#1cc88a">
                    <i class="fa fa-building"></i>
                </div>
            </div>
            <div class="card-footer bg-transparent border-0 pt-0">
                <a href="{{ route('departments.index') }}" class="small text-success">View all <i class="fa fa-arrow-right ms-1"></i></a>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6">
        <div class="card stat-card h-100">
            <div class="card-body d-flex align-items-center justify-content-between">
                <div>
                    <div class="text-muted small fw-semibold text-uppercase mb-1">Total Salary Slips</div>
                    <div class="h3 mb-0 fw-bold">{{ $totalSalarySlips }}</div>
                </div>
                <div class="stat-icon" style="background:rgba(246,194,62,.15);color:#f6c23e">
                    <i class="fa fa-money-bill-wave"></i>
                </div>
            </div>
            <div class="card-footer bg-transparent border-0 pt-0">
                <a href="{{ route('salary-slips.index') }}" class="small text-warning">View all <i class="fa fa-arrow-right ms-1"></i></a>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6">
        <div class="card stat-card h-100">
            <div class="card-body d-flex align-items-center justify-content-between">
                <div>
                    <div class="text-muted small fw-semibold text-uppercase mb-1">Total Offer Letters</div>
                    <div class="h3 mb-0 fw-bold">{{ $totalOfferLetters }}</div>
                </div>
                <div class="stat-icon" style="background:rgba(231,74,59,.15);color:#e74a3b">
                    <i class="fa fa-envelope-open-text"></i>
                </div>
            </div>
            <div class="card-footer bg-transparent border-0 pt-0">
                <a href="{{ route('offer-letters.index') }}" class="small text-danger">View all <i class="fa fa-arrow-right ms-1"></i></a>
            </div>
        </div>
    </div>
</div>

{{-- Charts --}}
<div class="row g-4">
    <div class="col-xl-7">
        <div class="card page-card">
            <div class="card-header bg-white d-flex justify-content-between align-items-center py-3">
                <h6 class="mb-0 fw-semibold"><i class="fa fa-chart-bar me-2 text-primary"></i>Department-wise Employee Count</h6>
            </div>
            <div class="card-body">
                <canvas id="deptChart" height="280"></canvas>
            </div>
        </div>
    </div>
    <div class="col-xl-5">
        <div class="card page-card">
            <div class="card-header bg-white d-flex justify-content-between align-items-center py-3">
                <h6 class="mb-0 fw-semibold"><i class="fa fa-chart-line me-2 text-success"></i>Monthly Joining (Last 12 Months)</h6>
            </div>
            <div class="card-body">
                <canvas id="joiningChart" height="280"></canvas>
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
const deptLabels   = @json($deptLabels);
const deptCounts   = @json($deptCounts);
const monthLabels  = @json($monthLabels);
const joiningCounts= @json($joiningCounts);

new Chart(document.getElementById('deptChart'), {
    type: 'bar',
    data: {
        labels: deptLabels,
        datasets: [{
            label: 'Employees',
            data: deptCounts,
            backgroundColor: 'rgba(78,115,223,.8)',
            borderColor: '#4e73df',
            borderWidth: 1,
            borderRadius: 6,
        }]
    },
    options: {
        responsive: true,
        plugins: { legend: { display: false } },
        scales: {
            y: { beginAtZero: true, ticks: { stepSize: 1 } },
            x: { grid: { display: false } }
        }
    }
});

new Chart(document.getElementById('joiningChart'), {
    type: 'line',
    data: {
        labels: monthLabels,
        datasets: [{
            label: 'Joinings',
            data: joiningCounts,
            borderColor: '#1cc88a',
            backgroundColor: 'rgba(28,200,138,.1)',
            borderWidth: 2,
            fill: true,
            tension: 0.4,
            pointBackgroundColor: '#1cc88a',
        }]
    },
    options: {
        responsive: true,
        plugins: { legend: { display: false } },
        scales: {
            y: { beginAtZero: true, ticks: { stepSize: 1 } },
            x: { grid: { display: false } }
        }
    }
});
</script>
@endpush
