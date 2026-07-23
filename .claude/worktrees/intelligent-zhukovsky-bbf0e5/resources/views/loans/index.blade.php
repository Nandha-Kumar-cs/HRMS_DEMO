@extends('layouts.app')
@section('title','Loans & Advances')
@section('breadcrumb')<li class="breadcrumb-item active">Loans & Advances</li>@endsection
@push('styles')<style>.btn-xs{padding:.2rem .5rem;font-size:.75rem}</style>@endpush

@section('content')
<div class="card page-card">
    <div class="card-header bg-white py-3 d-flex align-items-center justify-content-between">
        <h5 class="mb-0 fw-semibold"><i class="fa fa-hand-holding-dollar me-2 text-primary"></i>Loans & Advances</h5>
        <a href="{{ route('loans.create') }}" class="btn btn-primary btn-sm"><i class="fa fa-plus me-1"></i>Add Loan / Advance</a>
    </div>
    <div class="card-body">
        @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show">{{ session('success') }}<button class="btn-close" data-bs-dismiss="alert"></button></div>
        @endif

        {{-- Filters --}}
        <div class="row g-2 mb-3">
            <div class="col-md-3">
                <select id="filterEmployee" class="form-select form-select-sm">
                    <option value="">All Employees</option>
                    @foreach($employees as $e)
                        <option value="{{ $e->id }}">{{ $e->full_name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <select id="filterType" class="form-select form-select-sm">
                    <option value="">All Types</option>
                    <option value="loan">Loan</option>
                    <option value="advance">Advance</option>
                </select>
            </div>
            <div class="col-md-2">
                <select id="filterStatus" class="form-select form-select-sm">
                    <option value="">All Status</option>
                    <option value="active">Active</option>
                    <option value="closed">Closed</option>
                </select>
            </div>
        </div>

        <div class="table-responsive">
            <table id="loansTable" class="table table-striped table-hover table-bordered align-middle w-100" style="font-size:.87rem">
                <thead class="table-dark">
                    <tr>
                        <th>Employee</th>
                        <th>Type</th>
                        <th>Principal</th>
                        <th>Interest</th>
                        <th>Total Due</th>
                        <th>Monthly EMI</th>
                        <th>Returned</th>
                        <th>Pending</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
var table = $('#loansTable').DataTable({
    processing: true,
    serverSide: true,
    ajax: {
        url: '{{ route("loans.index") }}',
        data: function(d) {
            d.employee_id = $('#filterEmployee').val();
            d.type        = $('#filterType').val();
            d.status      = $('#filterStatus').val();
        }
    },
    columns: [
        { data: 'employee_name' },
        { data: 'type_badge', orderable: false },
        { data: 'amount',   render: v => '₹' + parseFloat(v).toLocaleString('en-IN', {minimumFractionDigits:2}) },
        { data: 'interest', render: v => parseFloat(v) > 0
            ? '<span class="text-warning">₹' + v + '</span>'
            : '<span class="text-muted">—</span>' },
        { data: 'total_due', render: v => '<strong>₹' + v + '</strong>' },
        { data: 'monthly_deduction', render: v => '₹' + parseFloat(v).toLocaleString('en-IN', {minimumFractionDigits:2}) },
        { data: 'returned', render: v => '<span class="text-success fw-semibold">₹' + v + '</span>' },
        { data: 'pending',  render: v => parseFloat(v.replace(/,/g,'')) > 0
            ? '<span class="text-danger fw-semibold">₹' + v + '</span>'
            : '<span class="text-success">₹' + v + '</span>' },
        { data: 'status_badge', orderable: false },
        { data: 'action', orderable: false, searchable: false }
    ],
    order: [[0, 'asc']]
});

$('#filterEmployee, #filterType, #filterStatus').on('change', function() {
    table.ajax.reload();
});

// Global delete handler
$(document).on('click', '.btn-delete', function() {
    var url = $(this).data('url');
    Swal.fire({
        title: 'Delete this loan record?',
        text: 'All associated repayment history will also be deleted.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#e74a3b',
        confirmButtonText: 'Yes, delete!'
    }).then(r => {
        if (r.isConfirmed) {
            $.ajax({
                url: url, type: 'DELETE',
                headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
                success: res => {
                    table.ajax.reload();
                    Swal.fire({ icon: 'success', title: res.message, timer: 1500, showConfirmButton: false });
                }
            });
        }
    });
});
</script>
@endpush
