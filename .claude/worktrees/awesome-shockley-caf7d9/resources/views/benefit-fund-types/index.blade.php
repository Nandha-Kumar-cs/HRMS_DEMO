@extends('layouts.app')
@section('title', 'Benefit Fund Types')
@section('breadcrumb')
    <li class="breadcrumb-item active">Benefit Fund Types</li>
@endsection

@section('content')
<div class="card page-card">
    <div class="card-header bg-white py-3 d-flex align-items-center justify-content-between">
        <h5 class="mb-0 fw-semibold"><i class="fa fa-hand-holding-heart me-2 text-primary"></i>Benefit Fund Types</h5>
        <button class="btn btn-sm btn-primary" id="btnAdd">
            <i class="fa fa-plus me-1"></i>Add Fund Type
        </button>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-bordered table-hover align-middle mb-0">
                <thead class="table-dark">
                    <tr>
                        <th style="width:50px">#</th>
                        <th>Fund Type</th>
                        <th>Description</th>
                        <th style="width:100px" class="text-center">Status</th>
                        <th style="width:120px" class="text-center">In Use</th>
                        <th style="width:140px" class="text-center">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($types as $i => $t)
                    <tr>
                        <td>{{ $i + 1 }}</td>
                        <td><span class="badge bg-{{ $t->color }} me-2">&nbsp;</span><span class="fw-semibold">{{ $t->name }}</span></td>
                        <td class="text-muted small">{{ $t->description ?: '—' }}</td>
                        <td class="text-center">
                            <span class="badge bg-{{ $t->status === 'active' ? 'success' : 'secondary' }}">{{ ucfirst($t->status) }}</span>
                        </td>
                        <td class="text-center">{{ $t->employee_benefits_count }}</td>
                        <td class="text-center">
                            <button class="btn btn-sm btn-outline-primary btn-edit"
                                    data-id="{{ $t->id }}"
                                    data-name="{{ $t->name }}"
                                    data-description="{{ $t->description }}"
                                    data-color="{{ $t->color }}"
                                    data-status="{{ $t->status }}">
                                <i class="fa fa-pen"></i>
                            </button>
                            <button class="btn btn-sm btn-outline-danger btn-delete" data-id="{{ $t->id }}">
                                <i class="fa fa-trash"></i>
                            </button>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="6" class="text-center text-muted py-4">No benefit fund types defined.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="modal fade" id="bftModal" tabindex="-1">
    <div class="modal-dialog">
        <form id="bftForm">
            @csrf
            <input type="hidden" name="id" id="bftId">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title fw-semibold" id="bftTitle">Add Benefit Fund Type</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Name <span class="text-danger">*</span></label>
                        <input type="text" name="name" id="bftName" class="form-control" required maxlength="100">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Description</label>
                        <textarea name="description" id="bftDesc" class="form-control" rows="2" maxlength="1000"></textarea>
                    </div>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Color <span class="text-danger">*</span></label>
                            <select name="color" id="bftColor" class="form-select">
                                <option value="primary">Primary (Blue)</option>
                                <option value="secondary">Secondary (Gray)</option>
                                <option value="success">Success (Green)</option>
                                <option value="danger">Danger (Red)</option>
                                <option value="warning">Warning (Yellow)</option>
                                <option value="info">Info (Cyan)</option>
                                <option value="dark">Dark</option>
                            </select>
                            <span class="badge bg-primary mt-2" id="bftPreview">Preview</span>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Status <span class="text-danger">*</span></label>
                            <select name="status" id="bftStatus" class="form-select">
                                <option value="active">Active</option>
                                <option value="inactive">Inactive</option>
                            </select>
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
    var modal = new bootstrap.Modal(document.getElementById('bftModal'));

    function reset() {
        $('#bftId,#bftName,#bftDesc').val('');
        $('#bftColor').val('primary'); $('#bftPreview').attr('class','badge bg-primary mt-2');
        $('#bftStatus').val('active');
        $('#bftTitle').text('Add Benefit Fund Type');
    }

    $('#btnAdd').on('click', function(){ reset(); modal.show(); });

    $('#bftColor').on('change', function(){
        $('#bftPreview').attr('class','badge bg-'+$(this).val()+' mt-2');
    });

    $(document).on('click','.btn-edit', function(){
        var b = $(this);
        $('#bftId').val(b.data('id'));
        $('#bftName').val(b.data('name'));
        $('#bftDesc').val(b.data('description'));
        $('#bftColor').val(b.data('color')).trigger('change');
        $('#bftStatus').val(b.data('status'));
        $('#bftTitle').text('Edit Benefit Fund Type');
        modal.show();
    });

    $('#bftForm').on('submit', function(e){
        e.preventDefault();
        var id  = $('#bftId').val();
        var url = id ? '{{ url('benefit-fund-types') }}/'+id : '{{ route('benefit-fund-types.store') }}';
        var method = id ? 'PUT' : 'POST';
        $.ajax({
            url, method,
            data: {
                name: $('#bftName').val(),
                description: $('#bftDesc').val(),
                color: $('#bftColor').val(),
                status: $('#bftStatus').val(),
                _token: '{{ csrf_token() }}',
            },
            success: function(){ modal.hide(); Swal.fire({icon:'success',title:'Saved',timer:1100,showConfirmButton:false}); setTimeout(()=>location.reload(),700); },
            error: function(xhr){
                var msg = xhr.responseJSON?.message || 'Failed';
                if (xhr.responseJSON?.errors) msg = Object.values(xhr.responseJSON.errors).flat().join(' ');
                Swal.fire({icon:'error',title:'Error',text:msg});
            }
        });
    });

    $(document).on('click','.btn-delete', function(){
        var id = $(this).data('id');
        Swal.fire({title:'Delete?',icon:'warning',showCancelButton:true,confirmButtonColor:'#d33'}).then(r=>{
            if(!r.isConfirmed) return;
            $.ajax({
                url:'{{ url('benefit-fund-types') }}/'+id,
                method:'DELETE',
                data:{_token:'{{ csrf_token() }}'},
                success:function(){ Swal.fire({icon:'success',title:'Deleted',timer:900,showConfirmButton:false}); setTimeout(()=>location.reload(),600); },
                error: function(xhr){ Swal.fire({icon:'error',title:'Error',text:xhr.responseJSON?.message||'Failed'}); }
            });
        });
    });
});
</script>
@endpush
