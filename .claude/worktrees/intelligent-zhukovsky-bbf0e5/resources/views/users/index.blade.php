@extends('layouts.app')
@section('title','User Management')
@section('breadcrumb')<li class="breadcrumb-item active">User Management</li>@endsection

@section('content')
<div class="card page-card">
    <div class="card-header bg-white py-3 d-flex align-items-center justify-content-between">
        <h5 class="mb-0 fw-semibold">User Management</h5>
        <a href="{{ route('users.create') }}" class="btn btn-primary btn-sm"><i class="fa fa-plus me-1"></i>Add User</a>
    </div>
    <div class="card-body">
        @if(session('success'))<div class="alert alert-success alert-dismissible fade show">{{ session('success') }}<button class="btn-close" data-bs-dismiss="alert"></button></div>@endif
        <div class="table-responsive">
            <table id="usersTable" class="table table-hover table-bordered align-middle">
                <thead class="table-dark">
                    <tr>
                        <th>#</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Created</th>
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
    var table = $('#usersTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: '{{ route("users.index") }}',
        columns: [
            { data: 'id' },
            { data: 'name' },
            { data: 'email' },
            { data: 'role_badge', orderable: false, searchable: false },
            { data: 'created_at' },
            { data: 'action', orderable: false, searchable: false },
        ]
    });

    $(document).on('click', '.btn-delete-user', function(){
        var url = $(this).data('url');
        Swal.fire({
            title: 'Delete User?',
            text: 'This action cannot be undone.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#e74a3b',
            confirmButtonText: 'Yes, delete'
        }).then(function(r){
            if (!r.isConfirmed) return;
            $.ajax({ url: url, type: 'DELETE',
                headers: {'X-CSRF-TOKEN': $('meta[name=csrf-token]').attr('content')},
                success: function(res){
                    if (res.success) { table.ajax.reload(); Swal.fire('Deleted!','','success'); }
                    else Swal.fire('Error', res.message, 'error');
                }
            });
        });
    });
});
</script>
@endpush
