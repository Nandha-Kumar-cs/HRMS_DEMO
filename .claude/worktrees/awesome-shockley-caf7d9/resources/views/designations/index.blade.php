@extends('layouts.app')

@section('title', 'Designations')

@section('breadcrumb')
    <li class="breadcrumb-item active">Designations</li>
@endsection

@section('content')
<div class="card page-card">
    <div class="card-header bg-white py-3 d-flex align-items-center justify-content-between">
        <h5 class="mb-0 fw-semibold">Designations</h5>
        <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#desigModal" onclick="openAddModal()">
            <i class="fa fa-plus me-1"></i> Add Designation
        </button>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table id="desigTable" class="table table-striped table-hover table-bordered align-middle w-100">
                <thead class="table-dark">
                    <tr>
                        <th>#</th>
                        <th>Designation Name</th>
                        <th>Department</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody></tbody>
                <tfoot><tr><th></th><th></th><th></th><th></th><th></th></tr></tfoot>
            </table>
        </div>
    </div>
</div>

<div class="modal fade" id="desigModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalTitle">Add Designation</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="formErrors" class="alert alert-danger d-none"></div>
                <input type="hidden" id="desigId">
                <div class="mb-3">
                    <label class="form-label">Designation Name <span class="text-danger">*</span></label>
                    <input type="text" id="desigName" class="form-control" placeholder="Enter designation name">
                </div>
                <div class="mb-3">
                    <label class="form-label">Department</label>
                    <select id="desigDept" class="form-select">
                        <option value="">Select Department</option>
                        @foreach($departments as $dept)
                            <option value="{{ $dept->id }}">{{ $dept->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label">Status</label>
                    <select id="desigStatus" class="form-select">
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                    </select>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" onclick="saveDesignation()">
                    <i class="fa fa-save me-1"></i> Save
                </button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
var table = $('#desigTable').DataTable({
    processing: true, serverSide: true,
    ajax: '{{ route("designations.index") }}',
    dom: 'lfrtip',
    columns: [
        { data: 'id' },
        { data: 'name' },
        { data: 'department_name', name: 'department.name' },
        { data: 'status_badge', name: 'status', orderable: false },
        { data: 'action', orderable: false, searchable: false }
    ],
    initComplete: function() {
        this.api().columns([1,2,3]).every(function(colIdx) {
            var col = this;
            $('<input type="text" class="form-control form-control-sm" placeholder="Search...">')
                .appendTo($('#desigTable tfoot th').eq(colIdx))
                .on('keyup change', function() { col.search(this.value).draw(); });
        });
    }
});

function openAddModal() {
    $('#modalTitle').text('Add Designation');
    $('#desigId').val(''); $('#desigName').val('');
    $('#desigDept').val(''); $('#desigStatus').val('active');
    $('#formErrors').addClass('d-none');
}

$(document).on('click', '.btn-edit-desig', function() {
    var id = $(this).data('id');
    $.get('/designations/' + id + '/edit', function(d) {
        $('#modalTitle').text('Edit Designation');
        $('#desigId').val(d.id); $('#desigName').val(d.name);
        $('#desigDept').val(d.department_id); $('#desigStatus').val(d.status);
        $('#formErrors').addClass('d-none');
        new bootstrap.Modal('#desigModal').show();
    });
});

function saveDesignation() {
    var id = $('#desigId').val();
    var url = id ? '/designations/' + id : '/designations';
    $.ajax({ url: url, type: id ? 'PUT' : 'POST',
        data: { name: $('#desigName').val(), department_id: $('#desigDept').val(), status: $('#desigStatus').val() },
        success: function(res) {
            if (res.success) {
                bootstrap.Modal.getInstance('#desigModal').hide();
                table.ajax.reload();
                Swal.fire({ icon: 'success', title: res.message, timer: 1500, showConfirmButton: false });
            }
        },
        error: function(xhr) {
            var errors = xhr.responseJSON?.errors;
            if (errors) $('#formErrors').html(Object.values(errors).flat().join('<br>')).removeClass('d-none');
        }
    });
}

$(document).on('click', '.btn-delete-desig', function() {
    var id = $(this).data('id');
    Swal.fire({ title: 'Delete Designation?', icon: 'warning', showCancelButton: true, confirmButtonColor: '#e74a3b', confirmButtonText: 'Yes, delete!' })
        .then(function(r) {
            if (r.isConfirmed) {
                $.ajax({ url: '/designations/' + id, type: 'DELETE',
                    success: function(res) { table.ajax.reload(); Swal.fire({ icon: 'success', title: res.message, timer: 1200, showConfirmButton: false }); }
                });
            }
        });
});
</script>
@endpush
