@extends('layouts.app')

@section('title', 'Add Employee')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('employees.index') }}" class="text-decoration-none">Employees</a></li>
    <li class="breadcrumb-item active">Add Employee</li>
@endsection

@section('content')
<div class="card page-card">
    <div class="card-header bg-white py-3 d-flex align-items-center gap-2">
        <a href="{{ route('employees.index') }}" class="btn btn-sm btn-outline-secondary">
            <i class="fa fa-arrow-left"></i>
        </a>
        <h5 class="mb-0 fw-semibold">Employee Details</h5>
    </div>
    <div class="card-body">
        <form action="{{ route('employees.store') }}" method="POST" enctype="multipart/form-data" novalidate>
            @csrf

            @if($errors->any())
                <div class="alert alert-danger">
                    <ul class="mb-0">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            {{-- Personal Info --}}
            <h6 class="text-primary fw-semibold mb-3 border-bottom pb-2">Personal Information</h6>
            <div class="row g-3 mb-4">
                <div class="col-md-4">
                    <label class="form-label">Employee Code <span class="text-danger">*</span></label>
                    <input type="text" name="employee_code" class="form-control @error('employee_code') is-invalid @enderror"
                           value="{{ old('employee_code') }}" required>
                    @error('employee_code')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-4">
                    <label class="form-label">Full Name <span class="text-danger">*</span></label>
                    <input type="text" name="full_name" class="form-control @error('full_name') is-invalid @enderror"
                           value="{{ old('full_name') }}" required>
                    @error('full_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-4">
                    <label class="form-label">Email <span class="text-danger">*</span></label>
                    <input type="email" name="email" class="form-control @error('email') is-invalid @enderror"
                           value="{{ old('email') }}" required>
                    @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-4">
                    <label class="form-label">Phone <span class="text-danger">*</span></label>
                    <input type="text" name="phone" class="form-control @error('phone') is-invalid @enderror"
                           value="{{ old('phone') }}" required>
                    @error('phone')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-4">
                    <label class="form-label">Date of Birth</label>
                    <input type="date" name="dob" class="form-control @error('dob') is-invalid @enderror"
                           value="{{ old('dob') }}">
                    @error('dob')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-4">
                    <label class="form-label">Gender</label>
                    <select name="gender" class="form-select @error('gender') is-invalid @enderror">
                        <option value="">Select Gender</option>
                        <option value="male" {{ old('gender') === 'male' ? 'selected' : '' }}>Male</option>
                        <option value="female" {{ old('gender') === 'female' ? 'selected' : '' }}>Female</option>
                        <option value="other" {{ old('gender') === 'other' ? 'selected' : '' }}>Other</option>
                    </select>
                    @error('gender')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
            </div>

            {{-- Employment Info --}}
            <h6 class="text-primary fw-semibold mb-3 border-bottom pb-2">Employment Information</h6>
            <div class="row g-3 mb-4">
                <div class="col-md-4">
                    <label class="form-label">Entity (Company)</label>
                    <select name="entity_id" class="form-select @error('entity_id') is-invalid @enderror">
                        <option value="">Select Entity</option>
                        @foreach($entities as $entity)
                            <option value="{{ $entity->id }}" {{ old('entity_id') == $entity->id ? 'selected' : '' }}>{{ $entity->name }}</option>
                        @endforeach
                    </select>
                    @error('entity_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-4">
                    <label class="form-label">Department</label>
                    <select name="department_id" id="department_id" class="form-select @error('department_id') is-invalid @enderror">
                        <option value="">Select Department</option>
                        @foreach($departments as $dept)
                            <option value="{{ $dept->id }}" {{ old('department_id') == $dept->id ? 'selected' : '' }}>{{ $dept->name }}</option>
                        @endforeach
                    </select>
                    @error('department_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-4">
                    <label class="form-label">Designation</label>
                    <select name="designation_id" id="designation_id" class="form-select @error('designation_id') is-invalid @enderror">
                        <option value="">Select Designation</option>
                        @foreach($designations as $desg)
                            <option value="{{ $desg->id }}" {{ old('designation_id') == $desg->id ? 'selected' : '' }}>{{ $desg->name }}</option>
                        @endforeach
                    </select>
                    @error('designation_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-4">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-select @error('status') is-invalid @enderror">
                        <option value="active" {{ old('status') === 'active' ? 'selected' : '' }}>Active</option>
                        <option value="inactive" {{ old('status') === 'inactive' ? 'selected' : '' }}>Inactive</option>
                        <option value="on_leave" {{ old('status') === 'on_leave' ? 'selected' : '' }}>On Leave</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Joining Date</label>
                    <input type="date" name="joining_date" class="form-control @error('joining_date') is-invalid @enderror"
                           value="{{ old('joining_date') }}">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Probation End Date</label>
                    <input type="date" name="probation_end" class="form-control @error('probation_end') is-invalid @enderror"
                           value="{{ old('probation_end') }}">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Reporting Manager</label>
                    <select name="reporting_manager_id" class="form-select @error('reporting_manager_id') is-invalid @enderror">
                        <option value="">Select Manager</option>
                        @foreach($managers as $mgr)
                            <option value="{{ $mgr->id }}" {{ old('reporting_manager_id') == $mgr->id ? 'selected' : '' }}>{{ $mgr->full_name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            {{-- Photo --}}
            <h6 class="text-primary fw-semibold mb-3 border-bottom pb-2">Photo</h6>
            <div class="row g-3 mb-4">
                <div class="col-md-4">
                    <label class="form-label">Employee Photo</label>
                    <input type="file" name="photo" id="photoInput" accept="image/jpeg,image/png,image/webp"
                           class="form-control @error('photo') is-invalid @enderror"
                           onchange="previewPhoto(this)">
                    <div class="form-text">JPG / PNG / WebP — max 2 MB</div>
                    @error('photo')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    <div id="photoPreviewWrap" class="mt-2 d-none">
                        <img id="photoPreview" src="" alt="Preview"
                             class="rounded" style="width:80px;height:80px;object-fit:cover;border:2px solid #e2e8f0">
                    </div>
                </div>
            </div>

            {{-- Salary --}}
            <h6 class="text-primary fw-semibold mb-3 border-bottom pb-2">Salary Structure</h6>
            <div class="alert alert-info alert-sm py-2 small mb-3">
                <i class="fa fa-info-circle me-1"></i>
                <strong>CTC per Month</strong> is the total monthly package. Basic, HRA, TA and other allowances are auto-calculated from this value when generating payslips.
            </div>
            <div class="row g-3 mb-4">
                <div class="col-md-4">
                    <label class="form-label">CTC per Month <span class="text-danger">*</span></label>
                    <div class="input-group">
                        <span class="input-group-text">₹</span>
                        <input type="number" name="fixed_salary" step="0.01" min="0"
                               class="form-control @error('fixed_salary') is-invalid @enderror"
                               value="{{ old('fixed_salary', 0) }}" required>
                        @error('fixed_salary')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="form-text">Total monthly cost to company (Basic + HRA + TA + allowances)</div>
                </div>
                <div class="col-md-4 d-flex align-items-end pb-1">
                    <div>
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" name="ot_enabled" value="1" id="otEnabled"
                                   {{ old('ot_enabled') ? 'checked' : '' }}>
                            <label class="form-check-label fw-semibold" for="otEnabled">
                                <i class="fa fa-clock me-1 text-warning"></i>OT Enabled
                            </label>
                        </div>
                        <div class="form-text">
                            Auto-calculate overtime when check-out exceeds 8:30 PM.<br>
                            OT rate = (Basic ÷ 30 ÷ 8) × 2 per OT hour.
                        </div>
                    </div>
                </div>
            </div>

            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary">
                    <i class="fa fa-save me-1"></i> Create Employee
                </button>
                <a href="{{ route('employees.index') }}" class="btn btn-light">Cancel</a>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
function previewPhoto(input) {
    if (input.files && input.files[0]) {
        var reader = new FileReader();
        reader.onload = function(e) {
            $('#photoPreview').attr('src', e.target.result);
            $('#photoPreviewWrap').removeClass('d-none');
        };
        reader.readAsDataURL(input.files[0]);
    }
}
$('#department_id').on('change', function() {
    var deptId = $(this).val();
    var $desig = $('#designation_id');
    $desig.html('<option value="">Select Designation</option>');
    if (!deptId) return;
    $.get('{{ route("designations.by-department", ":id") }}'.replace(':id', deptId), function(data) {
        $.each(data, function(i, d) {
            $desig.append('<option value="' + d.id + '">' + d.name + '</option>');
        });
    });
});
</script>
@endpush
