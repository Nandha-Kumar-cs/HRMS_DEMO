@extends('layouts.app')

@section('title', 'Employees')

@section('breadcrumb')
    <li class="breadcrumb-item active">Employees</li>
@endsection

@section('content')

{{-- Import result banner --}}
@if(session('import_result'))
@php $r = session('import_result'); @endphp
<div class="alert alert-info alert-dismissible fade show mb-3">
    <div class="d-flex align-items-center gap-3 mb-2">
        <i class="fa fa-file-excel fs-4 text-success"></i>
        <strong>Import Complete</strong>
    </div>
    <div class="d-flex gap-4 mb-2">
        <span><i class="fa fa-plus-circle text-success me-1"></i><strong>{{ $r['imported'] }}</strong> new employees created</span>
        <span><i class="fa fa-pen-to-square text-primary me-1"></i><strong>{{ $r['updated'] }}</strong> existing employees updated</span>
        <span><i class="fa fa-ban text-danger me-1"></i><strong>{{ $r['skipped'] }}</strong> rows skipped</span>
    </div>
    @if(!empty($r['errors']))
    <details>
        <summary class="text-danger fw-semibold small">{{ count($r['errors']) }} error(s) — click to view</summary>
        <ul class="mb-0 mt-2 small">
            @foreach($r['errors'] as $err)<li>{{ $err }}</li>@endforeach
        </ul>
    </details>
    @endif
    @if(!empty($r['warnings']))
    <details class="mt-1">
        <summary class="text-warning fw-semibold small">{{ count($r['warnings']) }} warning(s) — click to view</summary>
        <ul class="mb-0 mt-2 small">
            @foreach($r['warnings'] as $w)<li>{{ $w }}</li>@endforeach
        </ul>
    </details>
    @endif
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif

@if(session('import_error'))
<div class="alert alert-danger alert-dismissible fade show mb-3">
    <i class="fa fa-exclamation-triangle me-2"></i>{{ session('import_error') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif

<div class="card page-card">
    <div class="card-body">
        <div class="d-flex align-items-center justify-content-between mb-3">
            <div class="d-flex align-items-center gap-2">
                <label class="mb-0 text-muted small">Show</label>
                <select id="entriesSelect" class="form-select form-select-sm" style="width:auto">
                    <option value="10">10</option>
                    <option value="25">25</option>
                    <option value="50">50</option>
                    <option value="100">100</option>
                </select>
                <label class="mb-0 text-muted small">entries</label>
            </div>
            <h5 class="mb-0 fw-semibold">Employees List</h5>
            <div class="d-flex gap-2">
                <button class="btn btn-outline-success btn-sm" data-bs-toggle="modal" data-bs-target="#importModal">
                    <i class="fa fa-file-excel me-1"></i>Import Excel
                </button>
                <a href="{{ route('employees.create') }}" class="btn btn-primary btn-sm">
                    <i class="fa fa-plus me-1"></i> Add Employee
                </a>
            </div>
        </div>

        <div class="table-responsive">
            <table id="employeesTable" class="table table-striped table-hover table-bordered align-middle w-100">
                <thead class="table-dark">
                    <tr>
                        <th>Code</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Phone</th>
                        <th>Department</th>
                        <th>Designation</th>
                        <th>CTC</th>
                        <th>Variable</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody></tbody>
                <tfoot>
                    <tr>
                        <th></th><th></th><th></th><th></th>
                        <th></th><th></th><th></th><th></th>
                        <th></th><th></th>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
</div>

{{-- Import Modal --}}
<div class="modal fade" id="importModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-semibold"><i class="fa fa-file-excel me-2 text-success"></i>Import Employees from Excel</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                {{-- Instructions --}}
                <div class="alert alert-light border mb-3">
                    <div class="fw-semibold mb-2"><i class="fa fa-info-circle text-primary me-1"></i>How the import works:</div>
                    <ul class="mb-2 small">
                        <li>Upload an <strong>.xlsx</strong> or <strong>.xls</strong> file with your employee data.</li>
                        <li>Row 1 must be the heading row (matching the expected format).</li>
                        <li>If an <strong>Empcode</strong> already exists, the employee is <em>updated</em> — salary is never overwritten.</li>
                        <li>If the Empcode is new, the employee is <em>created</em> with ₹0 salary — you must set salary manually.</li>
                        <li><strong>DeptID / DesgID</strong> should match a department or designation <em>name</em> in the system (e.g. "IT", "Manager").</li>
                        <li>Date format: <code>dd/mm/yyyy</code> preferred (e.g. <code>15/05/1990</code>).</li>
                        <li>Fixed &amp; Variable Salary are <strong>not imported</strong> — enter them manually from the employee edit page.</li>
                    </ul>
                    <a href="{{ route('employees.import.template') }}" class="btn btn-sm btn-outline-success">
                        <i class="fa fa-download me-1"></i>Download Sample Template
                    </a>
                </div>

                {{-- Expected columns --}}
                <div class="mb-3">
                    <div class="fw-semibold small text-muted mb-1">Expected columns (in order):</div>
                    <div class="d-flex flex-wrap gap-1">
                        @foreach(['Empcode','Enroll No','Name','Father','Address','CountryCode','Mobile','Email','CompID','DeptID','DesgID','CatID','ActiveStatus','Shift','Auto Shift','WO1','WO2','Sat Off','Sat Half Day','Status','DOJ','ENT','CountryCode1','Mobile1','DOB','DOR'] as $col)
                        <span class="badge bg-light text-dark border" style="font-size:.72rem">{{ $col }}</span>
                        @endforeach
                    </div>
                    <div class="mt-1 small text-muted">
                        <span class="badge bg-success text-white" style="font-size:.7rem">Imported</span>
                        Empcode, Name, Mobile, Email, DeptID, DesgID, ActiveStatus, DOJ, DOB &nbsp;|&nbsp;
                        <span class="badge bg-secondary" style="font-size:.7rem">Ignored</span> all others
                    </div>
                </div>

                {{-- Upload form --}}
                <form action="{{ route('employees.import') }}" method="POST" enctype="multipart/form-data" id="importForm">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Select Excel File <span class="text-danger">*</span></label>
                        <input type="file" name="file" class="form-control" accept=".xlsx,.xls,.csv" required id="importFile">
                        <div class="form-text">Accepted: .xlsx, .xls, .csv — Max 5 MB</div>
                    </div>
                    <div id="importPreview" class="d-none alert alert-secondary small">
                        <i class="fa fa-file me-1"></i><span id="importFileName"></span>
                    </div>
                </form>
            </div>
            <div class="modal-footer border-0 pt-0">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" form="importForm" class="btn btn-success" id="btnImport">
                    <i class="fa fa-upload me-1"></i>Import Now
                </button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(function() {
    var table = $('#employeesTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: '{{ route("employees.index") }}',
        pageLength: 10,
        dom: 'rtip',
        columns: [
            { data: 'employee_code' },
            { data: 'full_name' },
            { data: 'email' },
            { data: 'phone' },
            { data: 'department_name', name: 'department.name' },
            { data: 'designation_name', name: 'designation.name' },
            { data: 'ctc', orderable: false, searchable: false },
            { data: 'variable', orderable: false, searchable: false },
            { data: 'status_badge', name: 'status', orderable: false },
            { data: 'action', orderable: false, searchable: false }
        ],
        initComplete: function() {
            var api = this.api();
            api.columns([0,1,2,3,4,5,8]).every(function(colIdx) {
                var col = this;
                $('<input type="text" class="form-control form-control-sm" placeholder="Search...">')
                    .appendTo($('#employeesTable tfoot th').eq(colIdx))
                    .on('keyup change', function() { col.search(this.value).draw(); });
            });
        }
    });

    $('#entriesSelect').on('change', function() {
        table.page.len($(this).val()).draw();
    });

    // Import file preview
    $('#importFile').on('change', function() {
        var f = this.files[0];
        if (f) {
            $('#importFileName').text(f.name + ' (' + (f.size / 1024).toFixed(1) + ' KB)');
            $('#importPreview').removeClass('d-none');
        }
    });

    // Prevent double-submit
    $('#importForm').on('submit', function() {
        $('#btnImport').prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span>Importing...');
    });

    // Delete
    $(document).on('click', '.btn-delete-employee', function() {
        var url = $(this).data('url');
        Swal.fire({
            title: 'Delete Employee?',
            text: 'This will also delete all associated records.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#e74a3b',
            confirmButtonText: 'Yes, delete it!'
        }).then(function(result) {
            if (result.isConfirmed) {
                $.ajax({ url: url, type: 'DELETE',
                    headers: {'X-CSRF-TOKEN': $('meta[name=csrf-token]').attr('content')},
                    success: function(res) {
                        if (res.success) {
                            Swal.fire('Deleted!', res.message, 'success');
                            table.ajax.reload();
                        }
                    }
                });
            }
        });
    });
});
</script>
@endpush
