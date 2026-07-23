@extends('layouts.app')
@section('title','Leave Requests')
@section('breadcrumb')<li class="breadcrumb-item active">Leave Requests</li>@endsection

@section('content')
<div class="card page-card">
    <div class="card-header bg-white py-3 d-flex align-items-center justify-content-between">
        <h5 class="mb-0 fw-semibold"><i class="fa fa-calendar-xmark me-2 text-primary"></i>Leave Requests</h5>
        <a href="{{ route('leave-requests.create') }}" class="btn btn-primary btn-sm"><i class="fa fa-plus me-1"></i>New Request</a>
    </div>
    <div class="card-body">
                <div class="table-responsive">
            <table id="leaveTable" class="table table-hover table-bordered align-middle">
                <thead class="table-dark">
                    <tr>
                        <th>Employee</th>
                        <th>Leave Type</th>
                        <th>Period</th>
                        <th>Days</th>
                        <th>Status</th>
                        <th></th>
                    </tr>
                </thead>
            </table>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(function(){
    var table = $('#leaveTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: '{{ route("leave-requests.index") }}',
        order: [[3, 'desc']],
        columns: [
            { data: 'employee_name',  orderable: false, searchable: true },
            { data: 'leave_type_name', orderable: false, searchable: false },
            { data: 'period',         orderable: false, searchable: false },
            { data: 'days_requested', orderable: true,  searchable: false },
            { data: 'status_badge',   orderable: false, searchable: false },
            { data: 'action',         orderable: false, searchable: false }
        ]
    });

    // SweetAlert2 confirm before delete
    $('#leaveTable').on('submit', '.leave-delete-form', function (e) {
        e.preventDefault();
        var form = this;
        Swal.fire({
            title: 'Delete Leave Request?',
            text: 'This action cannot be undone. Approved requests will have their balance and attendance automatically reversed.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc3545',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Yes, delete it',
            cancelButtonText: 'Cancel'
        }).then(function (result) {
            if (result.isConfirmed) form.submit();
        });
    });
});
</script>
@endpush
