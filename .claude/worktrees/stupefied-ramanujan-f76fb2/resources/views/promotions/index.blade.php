@extends('layouts.app')
@section('title','Promotions')
@section('breadcrumb')<li class="breadcrumb-item active">Promotions</li>@endsection
@push('styles')<style>.btn-xs{padding:.2rem .5rem;font-size:.75rem}</style>@endpush

@section('content')
<div class="card page-card">
    <div class="card-header bg-white py-3 d-flex align-items-center justify-content-between">
        <h5 class="mb-0 fw-semibold">Promotion History</h5>
        <a href="{{ route('promotions.create') }}" class="btn btn-primary btn-sm"><i class="fa fa-plus me-1"></i>Add Promotion</a>
    </div>
    <div class="card-body">
                <div class="row g-2 mb-3">
            <div class="col-md-4">
                <select id="filterEmployee" class="form-select form-select-sm">
                    <option value="">All Employees</option>
                    @foreach($employees as $e)<option value="{{ $e->id }}">{{ $e->full_name }} ({{ $e->employee_code }})</option>@endforeach
                </select>
            </div>
        </div>
        <div class="table-responsive">
            <table id="promotionsTable" class="table table-striped table-hover table-bordered align-middle w-100">
                <thead class="table-dark">
                    <tr><th>Employee</th><th>Effective Date</th><th>Previous Designation</th><th>New Designation</th><th>Department</th><th>Remarks</th><th>Actions</th></tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
    </div>
</div>
@endsection
@push('scripts')
<script>
var table = $('#promotionsTable').DataTable({
    processing:true, serverSide:true,
    ajax:{ url:'{{ route("promotions.index") }}', data:function(d){ d.employee_id = $('#filterEmployee').val(); } },
    columns:[
        { data:'employee_name' },
        { data:'effective_date_fmt' },
        { data:'prev_desig', defaultContent:'-' },
        { data:'new_desig' },
        { data:'dept', defaultContent:'-' },
        { data:'remarks', defaultContent:'-' },
        { data:'action', orderable:false, searchable:false }
    ]
});
$('#filterEmployee').on('change', function() { table.ajax.reload(); });
$(document).on('click', '.btn-delete', function() {
    var url = $(this).data('url');
    Swal.fire({title:'Delete Record?',icon:'warning',showCancelButton:true,confirmButtonColor:'#e74a3b',confirmButtonText:'Yes, delete!'})
        .then(r=>{ if(r.isConfirmed) $.ajax({url:url,type:'DELETE',success:res=>{ table.ajax.reload(); Swal.fire({icon:'success',title:res.message,timer:1200,showConfirmButton:false}); }}); });
});
</script>
@endpush
