@extends('layouts.app')

@section('title', 'Salary Components')

@section('breadcrumb')
    <li class="breadcrumb-item active">Salary Components</li>
@endsection

@section('content')
<div class="card page-card">
    <div class="card-header bg-white py-3 d-flex align-items-center justify-content-between">
        <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#compModal" onclick="openAddModal()">
            <i class="fa fa-plus me-1"></i> Add Component
        </button>
        <h5 class="mb-0 fw-semibold">Salary Components</h5>
        <div style="width:120px"></div>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table id="compsTable" class="table table-striped table-hover table-bordered align-middle w-100">
                <thead class="table-dark">
                    <tr>
                        <th>#</th>
                        <th>Name</th>
                        <th>Type</th>
                        <th>Calc. Type</th>
                        <th>Value</th>
                        <th>Formula</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody></tbody>
                <tfoot><tr><th></th><th></th><th></th><th></th><th></th><th></th><th></th></tr></tfoot>
            </table>
        </div>
    </div>
</div>

<div class="modal fade" id="compModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalTitle">Add Salary Component</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="formErrors" class="alert alert-danger d-none"></div>
                <input type="hidden" id="compId">
                <div class="mb-3">
                    <label class="form-label">Component Name <span class="text-danger">*</span></label>
                    <input type="text" id="compName" class="form-control" placeholder="e.g. HRA, Basic, PF">
                </div>
                <div class="mb-3">
                    <label class="form-label">Component Type <span class="text-danger">*</span></label>
                    <select id="compType" class="form-select">
                        <option value="allowance">Allowance</option>
                        <option value="deduction">Deduction</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label">Calculation Type <span class="text-danger">*</span></label>
                    <select id="compCalcType" class="form-select" onchange="updateFormulaPreview()">
                        <option value="percentage">Percentage of CTC</option>
                        <option value="fixed">Fixed Amount</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label">Value <span class="text-danger">*</span></label>
                    <input type="number" id="compValue" class="form-control" step="0.01" min="0" oninput="updateFormulaPreview()">
                </div>
                <div class="mb-3">
                    <label class="form-label">Formula Preview</label>
                    <div id="formulaPreview" class="form-control bg-light text-muted font-monospace" style="min-height:38px"></div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" onclick="saveComponent()">
                    <i class="fa fa-save me-1"></i> Save
                </button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
var table = $('#compsTable').DataTable({
    processing: true, serverSide: true,
    ajax: '{{ route("salary-components.index") }}',
    dom: 'lfrtip',
    columns: [
        { data: 'id' },
        { data: 'name' },
        { data: 'type_badge', name: 'type', orderable: false },
        { data: 'calculation_type' },
        { data: 'value' },
        { data: 'formula', orderable: false, searchable: false },
        { data: 'action', orderable: false, searchable: false }
    ],
    initComplete: function() {
        this.api().columns([1,2,3]).every(function(colIdx) {
            var col = this;
            $('<input type="text" class="form-control form-control-sm" placeholder="Search...">')
                .appendTo($('#compsTable tfoot th').eq(colIdx))
                .on('keyup change', function() { col.search(this.value).draw(); });
        });
    }
});

function openAddModal() {
    $('#modalTitle').text('Add Salary Component');
    $('#compId').val(''); $('#compName').val(''); $('#compType').val('allowance');
    $('#compCalcType').val('percentage'); $('#compValue').val('');
    $('#formulaPreview').text(''); $('#formErrors').addClass('d-none');
}

function updateFormulaPreview() {
    var name = ($('#compName').val() || 'component').toLowerCase().replace(/ /g,'_');
    var val  = $('#compValue').val() || '0';
    var calc = $('#compCalcType').val();
    if (calc === 'percentage') {
        $('#formulaPreview').text(name + ' = ' + val + ' / 100 * ctc');
    } else {
        $('#formulaPreview').text(name + ' = ' + val + ' (fixed amount)');
    }
}
$('#compName').on('input', updateFormulaPreview);

$(document).on('click', '.btn-edit-comp', function() {
    var id = $(this).data('id');
    $.get('/salary-components/' + id + '/edit', function(d) {
        $('#modalTitle').text('Edit Salary Component');
        $('#compId').val(d.id); $('#compName').val(d.name);
        $('#compType').val(d.type); $('#compCalcType').val(d.calculation_type);
        $('#compValue').val(d.value);
        updateFormulaPreview();
        $('#formErrors').addClass('d-none');
        new bootstrap.Modal('#compModal').show();
    });
});

function saveComponent() {
    var id = $('#compId').val();
    var url = id ? '/salary-components/' + id : '/salary-components';
    $.ajax({ url: url, type: id ? 'PUT' : 'POST',
        data: {
            name: $('#compName').val(), type: $('#compType').val(),
            calculation_type: $('#compCalcType').val(), value: $('#compValue').val()
        },
        success: function(res) {
            if (res.success) {
                bootstrap.Modal.getInstance('#compModal').hide();
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

$(document).on('click', '.btn-delete-comp', function() {
    var id = $(this).data('id');
    Swal.fire({ title: 'Delete Component?', icon: 'warning', showCancelButton: true, confirmButtonColor: '#e74a3b', confirmButtonText: 'Yes, delete!' })
        .then(function(r) {
            if (r.isConfirmed) {
                $.ajax({ url: '/salary-components/' + id, type: 'DELETE',
                    success: function(res) { table.ajax.reload(); Swal.fire({ icon: 'success', title: res.message, timer: 1200, showConfirmButton: false }); }
                });
            }
        });
});
</script>
@endpush
