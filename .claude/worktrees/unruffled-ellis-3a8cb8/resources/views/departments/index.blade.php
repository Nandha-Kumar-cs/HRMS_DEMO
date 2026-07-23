@extends('layouts.app')

@section('title', 'Departments')

@section('breadcrumb')
    <li class="breadcrumb-item active">Departments</li>
@endsection

@section('content')
<div class="card page-card">
    <div class="card-header bg-white py-3 d-flex align-items-center justify-content-between">
        <h5 class="mb-0 fw-semibold">Departments</h5>
        <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#deptModal" onclick="openAddModal()">
            <i class="fa fa-plus me-1"></i> Add Department
        </button>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table id="deptsTable" class="table table-striped table-hover table-bordered align-middle w-100">
                <thead class="table-dark">
                    <tr>
                        <th>#</th>
                        <th>Department Name</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody></tbody>
                <tfoot>
                    <tr><th></th><th></th><th></th><th></th></tr>
                </tfoot>
            </table>
        </div>
    </div>
</div>

<!-- Modal -->
<div class="modal fade" id="deptModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalTitle">Add Department</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="formErrors" class="alert alert-danger d-none"></div>
                <input type="hidden" id="deptId">
                <div class="mb-3">
                    <label class="form-label">Department Name <span class="text-danger">*</span></label>
                    <input type="text" id="deptName" class="form-control" placeholder="Enter department name">
                </div>
                <div class="mb-3">
                    <label class="form-label">Status</label>
                    <select id="deptStatus" class="form-select">
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                    </select>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" onclick="saveDepartment()">
                    <i class="fa fa-save me-1"></i> Save
                </button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
var table = $('#deptsTable').DataTable({
    processing: true,
    serverSide: true,
    ajax: '{{ route("departments.index") }}',
    dom: 'lfrtip',
    columns: [
        { data: 'id' },
        { data: 'name' },
        { data: 'status_badge', name: 'status', orderable: false },
        { data: 'action', orderable: false, searchable: false }
    ],
    initComplete: function() {
        var api = this.api();
        api.columns([1,2]).every(function(colIdx) {
            var col = this;
            $('<input type="text" class="form-control form-control-sm" placeholder="Search...">')
                .appendTo($('#deptsTable tfoot th').eq(colIdx))
                .on('keyup change', function() { col.search(this.value).draw(); });
        });
    }
});

function openAddModal() {
    $('#modalTitle').text('Add Department');
    $('#deptId').val('');
    $('#deptName').val('');
    $('#deptStatus').val('active');
    $('#formErrors').addClass('d-none');
}

$(document).on('click', '.btn-edit-dept', function() {
    var id = $(this).data('id');
    $.get('/departments/' + id + '/edit', function(d) {
        $('#modalTitle').text('Edit Department');
        $('#deptId').val(d.id);
        $('#deptName').val(d.name);
        $('#deptStatus').val(d.status);
        $('#formErrors').addClass('d-none');
        new bootstrap.Modal('#deptModal').show();
    });
});

function saveDepartment() {
    var id = $('#deptId').val();
    var url = id ? '/departments/' + id : '/departments';
    var method = id ? 'PUT' : 'POST';
    $.ajax({
        url: url, type: method,
        data: { name: $('#deptName').val(), status: $('#deptStatus').val() },
        success: function(res) {
            if (res.success) {
                bootstrap.Modal.getInstance('#deptModal').hide();
                table.ajax.reload();
                Swal.fire({ icon: 'success', title: res.message, timer: 1500, showConfirmButton: false });
            }
        },
        error: function(xhr) {
            var errors = xhr.responseJSON?.errors;
            if (errors) {
                var msgs = Object.values(errors).flat().join('<br>');
                $('#formErrors').html(msgs).removeClass('d-none');
            }
        }
    });
}

$(document).on('click', '.btn-delete-dept', function() {
    var id = $(this).data('id');
    Swal.fire({ title: 'Delete Department?', icon: 'warning', showCancelButton: true, confirmButtonColor: '#e74a3b', confirmButtonText: 'Yes, delete!' })
        .then(function(r) {
            if (r.isConfirmed) {
                $.ajax({ url: '/departments/' + id, type: 'DELETE',
                    success: function(res) { table.ajax.reload(); Swal.fire({ icon: 'success', title: res.message, timer: 1200, showConfirmButton: false }); }
                });
            }
        });
});
</script>
@endpush
