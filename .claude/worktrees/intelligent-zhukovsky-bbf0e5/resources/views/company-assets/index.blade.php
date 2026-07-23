@extends('layouts.app')
@section('title','Company Assets')
@section('breadcrumb')<li class="breadcrumb-item active">Company Assets</li>@endsection
@push('styles')<style>.btn-xs{padding:.2rem .5rem;font-size:.75rem}</style>@endpush

@section('content')
<div class="card page-card">
    <div class="card-header bg-white py-3 d-flex align-items-center justify-content-between">
        <h5 class="mb-0 fw-semibold">Company Assets</h5>
        <div class="d-flex gap-2">
            <a href="{{ route('no-due.index') }}" class="btn btn-outline-secondary btn-sm"><i class="fa fa-certificate me-1"></i>No Due Certificates</a>
            <a href="{{ route('assets.create') }}" class="btn btn-primary btn-sm"><i class="fa fa-plus me-1"></i>Add Asset</a>
        </div>
    </div>
    <div class="card-body">
        @if(session('success'))<div class="alert alert-success alert-dismissible fade show">{{ session('success') }}<button class="btn-close" data-bs-dismiss="alert"></button></div>@endif
        <div class="table-responsive">
            <table id="assetsTable" class="table table-striped table-hover table-bordered align-middle w-100">
                <thead class="table-dark">
                    <tr><th>Asset Name</th><th>Type</th><th>Serial No.</th><th>Status</th><th>Assigned To</th><th>Actions</th></tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
var table = $('#assetsTable').DataTable({
    processing:true, serverSide:true,
    ajax:'{{ route("assets.index") }}',
    columns:[
        { data:'asset_name' },
        { data:'type_label' },
        { data:'serial_number', defaultContent:'-' },
        { data:'status_badge', orderable:false },
        { data:'assigned_to', defaultContent:'-' },
        { data:'action', orderable:false, searchable:false }
    ]
});
$(document).on('click', '.btn-delete', function() {
    var url = $(this).data('url');
    Swal.fire({title:'Delete Asset?',icon:'warning',showCancelButton:true,confirmButtonColor:'#e74a3b',confirmButtonText:'Delete'})
        .then(r=>{ if(r.isConfirmed) $.ajax({url:url,type:'DELETE',success:res=>{ table.ajax.reload(); Swal.fire({icon:'success',title:res.message,timer:1200,showConfirmButton:false}); }}); });
});
</script>
@endpush
