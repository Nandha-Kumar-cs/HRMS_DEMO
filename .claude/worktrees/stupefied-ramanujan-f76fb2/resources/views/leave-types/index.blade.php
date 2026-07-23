@extends('layouts.app')
@section('title','Leave Types')
@section('breadcrumb')<li class="breadcrumb-item active">Leave Types</li>@endsection

@section('content')
<div class="card page-card">
    <div class="card-header bg-white py-3 d-flex align-items-center justify-content-between">
        <h5 class="mb-0 fw-semibold">Leave Types</h5>
        <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#leaveTypeModal" id="btnAdd">
            <i class="fa fa-plus me-1"></i>Add Leave Type
        </button>
    </div>
    <div class="card-body">
                <div class="table-responsive">
            <table class="table table-hover table-bordered align-middle" id="leaveTypesTable">
                <thead class="table-dark">
                    <tr><th>Name</th><th>Days/Year</th><th>Paid</th><th>Carry Forward</th><th>Status</th><th></th></tr>
                </thead>
                <tbody>
                    @foreach($types as $type)
                    <tr>
                        <td>{{ $type->name }}</td>
                        <td>{{ $type->days_allowed }}</td>
                        <td>@if($type->is_paid)<span class="badge bg-success">Paid</span>@else<span class="badge bg-secondary">Unpaid</span>@endif</td>
                        <td>@if($type->carry_forward)<span class="badge bg-info">Yes</span>@else<span class="badge bg-light text-dark">No</span>@endif</td>
                        <td><span class="badge bg-{{ $type->status === 'active' ? 'success' : 'danger' }}">{{ ucfirst($type->status) }}</span></td>
                        <td>
                            <button class="btn btn-sm btn-outline-primary btn-edit me-1"
                                data-id="{{ $type->id }}"
                                data-name="{{ $type->name }}"
                                data-days="{{ $type->days_allowed }}"
                                data-paid="{{ $type->is_paid ? 1 : 0 }}"
                                data-carry="{{ $type->carry_forward ? 1 : 0 }}"
                                data-status="{{ $type->status }}"
                                data-url="{{ route('leave-types.update', $type) }}">
                                <i class="fa fa-pen-to-square"></i>
                            </button>
                            <button class="btn btn-sm btn-outline-danger btn-delete" data-url="{{ route('leave-types.destroy', $type) }}">
                                <i class="fa fa-trash"></i>
                            </button>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>

{{-- Modal --}}
<div class="modal fade" id="leaveTypeModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalTitle">Add Leave Type</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="modalAlert" class="alert d-none"></div>
                <input type="hidden" id="editUrl">
                <input type="hidden" id="editMethod" value="POST">
                <div class="mb-3">
                    <label class="form-label">Name <span class="text-danger">*</span></label>
                    <input type="text" id="fieldName" class="form-control" placeholder="e.g. Annual Leave">
                </div>
                <div class="mb-3">
                    <label class="form-label">Days Allowed Per Year <span class="text-danger">*</span></label>
                    <input type="number" id="fieldDays" class="form-control" min="0" value="0">
                </div>
                <div class="row">
                    <div class="col">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="fieldPaid" checked>
                            <label class="form-check-label" for="fieldPaid">Paid Leave</label>
                        </div>
                    </div>
                    <div class="col">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="fieldCarry">
                            <label class="form-check-label" for="fieldCarry">Carry Forward</label>
                        </div>
                    </div>
                </div>
                <div class="mt-3">
                    <label class="form-label">Status</label>
                    <select id="fieldStatus" class="form-select">
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                    </select>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" id="btnSave" class="btn btn-primary">Save</button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
var storeUrl = '{{ route("leave-types.store") }}';

function resetModal(){
    $('#modalTitle').text('Add Leave Type');
    $('#fieldName').val('');
    $('#fieldDays').val(0);
    $('#fieldPaid').prop('checked', true);
    $('#fieldCarry').prop('checked', false);
    $('#fieldStatus').val('active');
    $('#editUrl').val('');
    $('#editMethod').val('POST');
    $('#modalAlert').addClass('d-none').text('');
}

$('#btnAdd').on('click', resetModal);

$(document).on('click', '.btn-edit', function(){
    resetModal();
    var d = $(this).data();
    $('#modalTitle').text('Edit Leave Type');
    $('#fieldName').val(d.name);
    $('#fieldDays').val(d.days);
    $('#fieldPaid').prop('checked', d.paid == 1);
    $('#fieldCarry').prop('checked', d.carry == 1);
    $('#fieldStatus').val(d.status);
    $('#editUrl').val(d.url);
    $('#editMethod').val('PUT');
    $('#leaveTypeModal').modal('show');
});

$('#btnSave').on('click', function(){
    var url    = $('#editUrl').val() || storeUrl;
    var method = $('#editMethod').val();
    $.ajax({
        url: url, type: 'POST',
        headers: {'X-CSRF-TOKEN': $('meta[name=csrf-token]').attr('content')},
        data: {
            _method: method,
            name: $('#fieldName').val(),
            days_allowed: $('#fieldDays').val(),
            is_paid: $('#fieldPaid').is(':checked') ? 1 : 0,
            carry_forward: $('#fieldCarry').is(':checked') ? 1 : 0,
            status: $('#fieldStatus').val()
        },
        success: function(res){
            if (res.success) { location.reload(); }
            else { $('#modalAlert').removeClass('d-none').addClass('alert-danger').text(res.message || 'Error'); }
        },
        error: function(xhr){
            var errors = xhr.responseJSON?.errors;
            var msg = errors ? Object.values(errors).flat().join(' ') : 'Validation error.';
            $('#modalAlert').removeClass('d-none').addClass('alert-danger').text(msg);
        }
    });
});

$(document).on('click', '.btn-delete', function(){
    var url = $(this).data('url');
    Swal.fire({ title:'Delete?', text:'This cannot be undone.', icon:'warning', showCancelButton:true, confirmButtonColor:'#e74a3b', confirmButtonText:'Delete' })
    .then(function(r){ if (!r.isConfirmed) return;
        $.ajax({ url: url, type: 'DELETE',
            headers: {'X-CSRF-TOKEN': $('meta[name=csrf-token]').attr('content')},
            success: function(res){ if(res.success) location.reload(); }
        });
    });
});
</script>
@endpush
