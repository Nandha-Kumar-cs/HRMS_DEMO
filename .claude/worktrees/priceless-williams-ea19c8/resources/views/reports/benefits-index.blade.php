@extends('layouts.app')
@section('title', 'Benefit & Bonus Reports')
@section('breadcrumb')
    <li class="breadcrumb-item active">Reports</li>
@endsection

@section('content')
<div class="row g-3">
    <div class="col-md-6 col-lg-3">
        <a href="{{ route('reports.monthly-benefits') }}" class="card text-decoration-none border-0 shadow-sm h-100">
            <div class="card-body text-center py-4">
                <i class="fa fa-gift fs-1 text-primary mb-3"></i>
                <h6 class="fw-bold text-dark">Monthly Benefit Report</h6>
                <small class="text-muted">All active benefits per fund type for a given month</small>
            </div>
        </a>
    </div>
    <div class="col-md-6 col-lg-3">
        <a href="{{ route('reports.bonuses') }}" class="card text-decoration-none border-0 shadow-sm h-100">
            <div class="card-body text-center py-4">
                <i class="fa fa-trophy fs-1 text-warning mb-3"></i>
                <h6 class="fw-bold text-dark">Bonus & Incentive Report</h6>
                <small class="text-muted">Approved bonuses by type for a payroll month</small>
            </div>
        </a>
    </div>
    <div class="col-md-6 col-lg-3">
        <a href="{{ route('reports.employee-history') }}" class="card text-decoration-none border-0 shadow-sm h-100">
            <div class="card-body text-center py-4">
                <i class="fa fa-user-clock fs-1 text-info mb-3"></i>
                <h6 class="fw-bold text-dark">Employee Benefit History</h6>
                <small class="text-muted">Full benefit & bonus history for an employee</small>
            </div>
        </a>
    </div>
    <div class="col-md-6 col-lg-3">
        <a href="{{ route('reports.payroll-impact') }}" class="card text-decoration-none border-0 shadow-sm h-100">
            <div class="card-body text-center py-4">
                <i class="fa fa-chart-line fs-1 text-success mb-3"></i>
                <h6 class="fw-bold text-dark">Payroll Impact Summary</h6>
                <small class="text-muted">Total impact of benefits + bonuses on payroll</small>
            </div>
        </a>
    </div>
</div>
@endsection
