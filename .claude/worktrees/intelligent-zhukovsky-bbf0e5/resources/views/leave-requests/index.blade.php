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
        @if(session('success'))<div class="alert alert-success alert-dismissible fade show">{{ session('success') }}<button class="btn-close" data-bs-dismiss="alert"></button></div>@endif
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
    $('#leaveTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: '{{ route("leave-requests.index") }}',
        order: [[6,'desc']],
        columns: [
            { data: 'employee_name', orderable: false },
            { data: 'leave_type_name' },
            { data: 'period', orderable: false },
            { data: 'days_requested' },
            { data: 'status_badge', orderable: false, searchable: false },
            { data: 'action', orderable: false, searchable: false },
            { data: 'created_at', visible: false }
        ]
    });
});
</script>
@endpush
