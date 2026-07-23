@extends('layouts.app')

@section('title', 'Entities')

@section('breadcrumb')
    <li class="breadcrumb-item active">Entities</li>
@endsection

@section('content')
<div class="card page-card">
    <div class="card-header bg-white py-3 d-flex align-items-center justify-content-between">
        <h5 class="mb-0 fw-semibold">Entities (Companies)</h5>
        <a href="{{ route('entities.create') }}" class="btn btn-primary btn-sm">
            <i class="fa fa-plus me-1"></i> Add Entity
        </a>
    </div>
    <div class="card-body">
        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show">{{ session('success') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
        @endif
        <div class="table-responsive">
            <table id="entitiesTable" class="table table-striped table-hover table-bordered align-middle w-100">
                <thead class="table-dark">
                    <tr>
                        <th>#</th>
                        <th>Entity Name</th>
                        <th>City</th>
                        <th>Phone</th>
                        <th>Signatory</th>
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
var table = $('#entitiesTable').DataTable({
    processing: true,
    serverSide: true,
    ajax: '{{ route("entities.index") }}',
    dom: 'lfrtip',
    columns: [
        { data: 'id' },
        { data: 'name' },
        { data: 'city', defaultContent: '-' },
        { data: 'phone', defaultContent: '-' },
        { data: 'signatory_name', defaultContent: '-' },
        { data: 'action', orderable: false, searchable: false }
    ]
});

$(document).on('click', '.btn-delete-entity', function() {
    var id = $(this).data('id');
    Swal.fire({ title: 'Delete Entity?', icon: 'warning', showCancelButton: true, confirmButtonColor: '#e74a3b', confirmButtonText: 'Yes, delete!' })
        .then(function(r) {
            if (r.isConfirmed) {
                $.ajax({ url: '/entities/' + id, type: 'DELETE',
                    success: function(res) {
                        table.ajax.reload();
                        Swal.fire({ icon: 'success', title: res.message, timer: 1200, showConfirmButton: false });
                    }
                });
            }
        });
});
</script>
@endpush
