@extends('layouts.app')
@section('title','Employee Documents')
@section('breadcrumb')<li class="breadcrumb-item active">Employee Documents</li>@endsection

@section('content')
<div class="row g-3">
    {{-- Upload Panel --}}
    <div class="col-md-4">
        <div class="card page-card">
            <div class="card-header bg-white py-3"><h6 class="mb-0 fw-semibold">Upload Document</h6></div>
            <div class="card-body">
                                @if(session('error'))<div class="alert alert-danger">{{ session('error') }}</div>@endif
                @if($errors->any())<div class="alert alert-danger"><ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>@endif

                <form action="{{ route('employee-documents.store') }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label">Employee <span class="text-danger">*</span></label>
                        <select name="employee_id" id="empFilter" class="form-select @error('employee_id') is-invalid @enderror">
                            <option value="">Select Employee</option>
                            @foreach($employees as $e)
                                <option value="{{ $e->id }}" {{ (old('employee_id') ?? $selected?->id) == $e->id ? 'selected' : '' }}>{{ $e->full_name }} ({{ $e->employee_code }})</option>
                            @endforeach
                        </select>
                        @error('employee_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Document Type <span class="text-danger">*</span></label>
                        <select name="document_type" class="form-select @error('document_type') is-invalid @enderror">
                            <option value="">Select Type</option>
                            @foreach(\App\Models\EmployeeDocument::$types as $val => $label)
                                <option value="{{ $val }}" {{ old('document_type') === $val ? 'selected' : '' }}>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Document Name <span class="text-danger">*</span></label>
                        <input type="text" name="document_name" class="form-control @error('document_name') is-invalid @enderror"
                               value="{{ old('document_name') }}" placeholder="e.g. Aadhaar Card">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">File <span class="text-danger">*</span></label>
                        <input type="file" name="file" class="form-control @error('file') is-invalid @enderror">
                        <div class="form-text">Max 10MB. PDF, JPG, PNG, DOCX allowed.</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Remarks</label>
                        <textarea name="remarks" class="form-control" rows="2">{{ old('remarks') }}</textarea>
                    </div>
                    <button type="submit" class="btn btn-primary w-100"><i class="fa fa-upload me-1"></i>Upload Document</button>
                </form>
            </div>
        </div>
    </div>

    {{-- Documents List --}}
    <div class="col-md-8">
        <div class="card page-card">
            <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                <h6 class="mb-0 fw-semibold">
                    @if($selected) Documents — {{ $selected->full_name }} @else All Documents @endif
                </h6>
                <div class="d-flex gap-2 align-items-center">
                    <select id="quickFilter" class="form-select form-select-sm" style="width:200px">
                        <option value="">Filter by Employee</option>
                        @foreach($employees as $e)<option value="{{ $e->id }}" {{ $selected?->id == $e->id ? 'selected' : '' }}>{{ $e->full_name }}</option>@endforeach
                    </select>
                </div>
            </div>
            <div class="card-body p-0">
                @if(($selected ? $documents->count() : $documents->total()) === 0)
                    <p class="text-muted text-center py-5">No documents found.</p>
                @else
                <div class="table-responsive">
                    <table class="table table-hover table-bordered align-middle mb-0">
                        <thead class="table-dark"><tr><th>Employee</th><th>Type</th><th>Name</th><th>Size</th><th>Uploaded</th><th></th></tr></thead>
                        <tbody>
                            @foreach($documents as $doc)
                            <tr>
                                @if(!$selected)<td>{{ $doc->employee->full_name }}</td>@endif
                                @if($selected)<td>{{ $doc->employee->full_name }}</td>@endif
                                <td><span class="badge bg-secondary" style="font-size:.75em">{{ $doc->type_label }}</span></td>
                                <td>{{ $doc->document_name }}</td>
                                <td class="text-muted">{{ $doc->file_size_human }}</td>
                                <td class="text-muted">{{ $doc->created_at->format('d M Y') }}</td>
                                <td class="text-nowrap">
                                    <a href="{{ route('employee-documents.preview', $doc) }}" class="btn btn-sm btn-outline-info" target="_blank" title="View document"><i class="fa fa-eye"></i></a>
                                    <a href="{{ route('employee-documents.download', $doc) }}" class="btn btn-sm btn-outline-primary ms-1" title="Download"><i class="fa fa-download"></i></a>
                                    <button class="btn btn-sm btn-outline-danger btn-del-doc ms-1" data-id="{{ $doc->id }}" title="Delete"><i class="fa fa-trash"></i></button>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @if(!$selected && $documents->hasPages())
                    <div class="px-3 py-2">{{ $documents->links() }}</div>
                @endif
                @endif
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
$('#quickFilter').on('change', function() {
    var id = $(this).val();
    var url = '{{ route("employee-documents.index") }}';
    window.location.href = id ? url + '?employee=' + id : url;
});

$(document).on('click', '.btn-del-doc', function() {
    var id = $(this).data('id');
    Swal.fire({title:'Delete document?',text:'This cannot be undone.',icon:'warning',showCancelButton:true,confirmButtonColor:'#e74a3b',confirmButtonText:'Delete'})
        .then(r => {
            if (r.isConfirmed) {
                $.ajax({ url: '/employee-documents/' + id, type: 'DELETE',
                    success: function(res) {
                        Swal.fire({icon:'success',title:'Deleted',timer:1200,showConfirmButton:false})
                            .then(() => location.reload());
                    }
                });
            }
        });
});
</script>
@endpush
