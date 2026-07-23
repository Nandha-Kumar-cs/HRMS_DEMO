@extends('layouts.app')
@section('title', 'Holiday Types')
@section('breadcrumb')
    <li class="breadcrumb-item active">Holiday Types</li>
@endsection

@section('content')
<div class="card page-card">
    <div class="card-header bg-white py-3 d-flex align-items-center justify-content-between">
        <h5 class="mb-0 fw-semibold"><i class="fa fa-tags me-2 text-primary"></i>Holiday Types</h5>
        <button class="btn btn-sm btn-primary" id="btnAddType">
            <i class="fa fa-plus me-1"></i>Add Type
        </button>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-bordered table-hover align-middle mb-0">
                <thead class="table-dark">
                    <tr>
                        <th style="width:50px">#</th>
                        <th>Type Name</th>
                        <th style="width:120px">Color</th>
                        <th style="width:140px" class="text-center">Holidays Using</th>
                        <th style="width:140px" class="text-center">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($types as $i => $t)
                    <tr data-id="{{ $t->id }}">
                        <td>{{ $i + 1 }}</td>
                        <td class="fw-semibold"><span class="badge bg-{{ $t->color }} me-2">&nbsp;</span>{{ $t->name }}</td>
                        <td><code>{{ $t->color }}</code></td>
                        <td class="text-center">{{ $t->holidays_count }}</td>
                        <td class="text-center">
                            <button class="btn btn-sm btn-outline-primary btn-edit"
                                    data-id="{{ $t->id }}"
                                    data-name="{{ $t->name }}"
                                    data-color="{{ $t->color }}">
                                <i class="fa fa-pen"></i>
                            </button>
                            <button class="btn btn-sm btn-outline-danger btn-delete" data-id="{{ $t->id }}">
                                <i class="fa fa-trash"></i>
                            </button>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="5" class="text-center text-muted py-4">No holiday types defined.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

{{-- Modal --}}
<div class="modal fade" id="typeModal" tabindex="-1">
    <div class="modal-dialog">
        <form id="typeForm">
            @csrf
            <input type="hidden" name="id" id="typeId">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title fw-semibold" id="typeModalTitle">Add Holiday Type</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Name <span class="text-danger">*</span></label>
                        <input type="text" name="name" id="typeName" class="form-control" required maxlength="80">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Color <span class="text-danger">*</span></label>
                        <select name="color" id="typeColor" class="form-select" required>
                            <option value="primary">Primary (Blue)</option>
                            <option value="secondary">Secondary (Gray)</option>
                            <option value="success">Success (Green)</option>
                            <option value="danger">Danger (Red)</option>
                            <option value="warning">Warning (Yellow)</option>
                            <option value="info">Info (Cyan)</option>
                            <option value="dark">Dark (Black)</option>
                        </select>
                        <div class="mt-2">
                            <span class="badge bg-primary me-1" id="colorPreview">Preview</span>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary"><i class="fa fa-save me-1"></i>Save</button>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(function(){
    var modal = new bootstrap.Modal(document.getElementById('typeModal'));

    function resetForm() {
        $('#typeId').val('');
        $('#typeName').val('');
        $('#typeColor').val('primary');
        $('#colorPreview').attr('class', 'badge bg-primary me-1');
        $('#typeModalTitle').text('Add Holiday Type');
    }

    $('#btnAddType').on('click', function(){
        resetForm();
        modal.show();
    });

    $('#typeColor').on('change', function(){
        $('#colorPreview').attr('class', 'badge bg-' + $(this).val() + ' me-1');
    });

    $(document).on('click', '.btn-edit', function(){
        var $b = $(this);
        $('#typeId').val($b.data('id'));
        $('#typeName').val($b.data('name'));
        $('#typeColor').val($b.data('color')).trigger('change');
        $('#typeModalTitle').text('Edit Holiday Type');
        modal.show();
    });

    $('#typeForm').on('submit', function(e){
        e.preventDefault();
        var id = $('#typeId').val();
        var url = id ? '{{ url('holiday-types') }}/' + id : '{{ route('holiday-types.store') }}';
        var method = id ? 'PUT' : 'POST';
        $.ajax({
            url: url,
            method: method,
            data: { name: $('#typeName').val(), color: $('#typeColor').val(), _token: '{{ csrf_token() }}' },
            success: function(){
                modal.hide();
                Swal.fire({icon:'success', title:'Saved', timer:1200, showConfirmButton:false});
                setTimeout(function(){ location.reload(); }, 800);
            },
            error: function(xhr){
                var msg = xhr.responseJSON?.message || 'Failed to save.';
                if (xhr.responseJSON?.errors) {
                    msg = Object.values(xhr.responseJSON.errors).flat().join(' ');
                }
                Swal.fire({icon:'error', title:'Error', text: msg});
            }
        });
    });

    $(document).on('click', '.btn-delete', function(){
        var id = $(this).data('id');
        Swal.fire({
            title: 'Delete this type?',
            text: 'Holidays using this type will keep their record but lose the type label.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Delete',
            confirmButtonColor: '#d33'
        }).then(function(r){
            if (!r.isConfirmed) return;
            $.ajax({
                url: '{{ url('holiday-types') }}/' + id,
                method: 'DELETE',
                data: { _token: '{{ csrf_token() }}' },
                success: function(){
                    Swal.fire({icon:'success', title:'Deleted', timer:1000, showConfirmButton:false});
                    setTimeout(function(){ location.reload(); }, 600);
                },
                error: function(xhr){
                    var msg = xhr.responseJSON?.message || 'Delete failed.';
                    Swal.fire({icon:'error', title:'Error', text: msg});
                }
            });
        });
    });
});
</script>
@endpush
