@extends('layouts.app')

@section('title', 'Edit Employee')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('employees.index') }}" class="text-decoration-none">Employees</a></li>
    <li class="breadcrumb-item active">Edit Employee</li>
@endsection

@section('content')
<div class="card page-card">
    <div class="card-header bg-white py-3 d-flex align-items-center gap-2">
        <a href="{{ route('employees.index') }}" class="btn btn-sm btn-outline-secondary">
            <i class="fa fa-arrow-left"></i>
        </a>
        <h5 class="mb-0 fw-semibold">Edit Employee — {{ $employee->full_name }}</h5>
    </div>
    <div class="card-body">
        <form action="{{ route('employees.update', $employee) }}" method="POST" enctype="multipart/form-data" novalidate>
            @csrf @method('PUT')

            @if($errors->any())
                <div class="alert alert-danger">
                    <ul class="mb-0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
                </div>
            @endif

            <h6 class="text-primary fw-semibold mb-3 border-bottom pb-2">Personal Information</h6>
            <div class="row g-3 mb-4">
                <div class="col-md-4">
                    <label class="form-label">Employee Code <span class="text-danger">*</span></label>
                    <input type="text" name="employee_code" class="form-control @error('employee_code') is-invalid @enderror"
                           value="{{ old('employee_code', $employee->employee_code) }}" required>
                    @error('employee_code')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-4">
                    <label class="form-label">Full Name <span class="text-danger">*</span></label>
                    <input type="text" name="full_name" class="form-control @error('full_name') is-invalid @enderror"
                           value="{{ old('full_name', $employee->full_name) }}" required>
                    @error('full_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-4">
                    <label class="form-label">Email <span class="text-danger">*</span></label>
                    <input type="email" name="email" class="form-control @error('email') is-invalid @enderror"
                           value="{{ old('email', $employee->email) }}" required>
                    @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-4">
                    <label class="form-label">Phone <span class="text-danger">*</span></label>
                    <input type="text" name="phone" class="form-control @error('phone') is-invalid @enderror"
                           value="{{ old('phone', $employee->phone) }}" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Date of Birth</label>
                    <input type="date" name="dob" class="form-control"
                           value="{{ old('dob', $employee->dob?->format('Y-m-d')) }}">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Gender</label>
                    <select name="gender" class="form-select">
                        <option value="">Select</option>
                        <option value="male" {{ old('gender', $employee->gender) === 'male' ? 'selected' : '' }}>Male</option>
                        <option value="female" {{ old('gender', $employee->gender) === 'female' ? 'selected' : '' }}>Female</option>
                        <option value="other" {{ old('gender', $employee->gender) === 'other' ? 'selected' : '' }}>Other</option>
                    </select>
                </div>
            </div>

            <h6 class="text-primary fw-semibold mb-3 border-bottom pb-2">Employment Information</h6>
            <div class="row g-3 mb-4">
                <div class="col-md-4">
                    <label class="form-label">Entity (Company)</label>
                    <select name="entity_id" class="form-select">
                        <option value="">Select Entity</option>
                        @foreach($entities as $entity)
                            <option value="{{ $entity->id }}" {{ old('entity_id', $employee->entity_id) == $entity->id ? 'selected' : '' }}>{{ $entity->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Department</label>
                    <select name="department_id" id="department_id" class="form-select">
                        <option value="">Select Department</option>
                        @foreach($departments as $dept)
                            <option value="{{ $dept->id }}" {{ old('department_id', $employee->department_id) == $dept->id ? 'selected' : '' }}>{{ $dept->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Designation</label>
                    <select name="designation_id" id="designation_id" class="form-select">
                        <option value="">Select Designation</option>
                        @foreach($designations as $desg)
                            <option value="{{ $desg->id }}" {{ old('designation_id', $employee->designation_id) == $desg->id ? 'selected' : '' }}>{{ $desg->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-select">
                        <option value="active" {{ old('status', $employee->status) === 'active' ? 'selected' : '' }}>Active</option>
                        <option value="inactive" {{ old('status', $employee->status) === 'inactive' ? 'selected' : '' }}>Inactive</option>
                        <option value="on_leave" {{ old('status', $employee->status) === 'on_leave' ? 'selected' : '' }}>On Leave</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Joining Date</label>
                    <input type="date" name="joining_date" class="form-control"
                           value="{{ old('joining_date', $employee->joining_date?->format('Y-m-d')) }}">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Probation End Date</label>
                    <input type="date" name="probation_end" class="form-control"
                           value="{{ old('probation_end', $employee->probation_end?->format('Y-m-d')) }}">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Reporting Manager</label>
                    <select name="reporting_manager_id" class="form-select">
                        <option value="">Select Manager</option>
                        @foreach($managers as $mgr)
                            <option value="{{ $mgr->id }}" {{ old('reporting_manager_id', $employee->reporting_manager_id) == $mgr->id ? 'selected' : '' }}>{{ $mgr->full_name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            {{-- Photo --}}
            <h6 class="text-primary fw-semibold mb-3 border-bottom pb-2">Photo</h6>
            <div class="row g-3 mb-4">
                <div class="col-md-4">
                    <label class="form-label">Employee Photo</label>
                    <div class="d-flex align-items-center gap-3 mb-2">
                        @if($employee->photo_path)
                            <img id="photoPreview" src="{{ asset('storage/' . $employee->photo_path) }}" alt="Photo"
                                 class="rounded" style="width:72px;height:72px;object-fit:cover;border:2px solid #e2e8f0">
                        @else
                            <div id="photoPreview" class="rounded d-flex align-items-center justify-content-center fw-bold text-white"
                                 style="width:72px;height:72px;background:#3b82f6;font-size:1.6rem;border:2px solid #e2e8f0;flex-shrink:0">
                                {{ strtoupper(substr($employee->full_name, 0, 1)) }}
                            </div>
                        @endif
                        <div>
                            <input type="file" name="photo" accept="image/jpeg,image/png,image/webp"
                                   class="form-control form-control-sm @error('photo') is-invalid @enderror"
                                   onchange="previewEditPhoto(this)">
                            <div class="form-text">JPG / PNG / WebP — max 2 MB. Leave blank to keep current.</div>
                            @error('photo')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            @if($employee->photo_path)
                            <div class="mt-2">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="remove_photo" value="1" id="removePhoto"
                                           onchange="handleRemovePhoto(this)">
                                    <label class="form-check-label text-danger small fw-semibold" for="removePhoto">
                                        <i class="fa fa-trash me-1"></i>Remove current photo
                                    </label>
                                </div>
                            </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            <h6 class="text-primary fw-semibold mb-3 border-bottom pb-2">Salary Structure</h6>

            @if($hasIncrements)
            <div class="alert alert-warning d-flex align-items-start gap-2 mb-3 py-2">
                <i class="fa fa-lock mt-1 text-warning"></i>
                <div>
                    <strong>CTC is managed via Increments.</strong>
                    This employee has salary revision history. To change the CTC, use
                    <a href="{{ route('increments.create', ['employee_id' => $employee->id]) }}" class="fw-semibold">Payroll → Increments</a>.
                </div>
            </div>
            @endif

            <div class="row g-3 mb-4">
                <div class="col-md-4">
                    <label class="form-label">CTC per Month <span class="text-danger">*</span></label>
                    <div class="input-group">
                        <span class="input-group-text">₹</span>
                        <input type="number" name="fixed_salary" step="0.01" min="0"
                               class="form-control @error('fixed_salary') is-invalid @enderror"
                               value="{{ old('fixed_salary', $employee->fixed_salary) }}"
                               {{ $hasIncrements ? 'readonly' : '' }} required>
                    </div>
                    @if($hasIncrements)
                    <div class="form-text text-warning"><i class="fa fa-lock me-1"></i>Locked — change via Increments page</div>
                    @else
                    <div class="form-text">Total monthly package (Basic + HRA + TA + allowances)</div>
                    @endif
                </div>
                <div class="col-md-4">
                    <label class="form-label">Variable / Performance Pay</label>
                    <div class="input-group">
                        <span class="input-group-text">₹</span>
                        <input type="number" name="variable_salary" step="0.01" min="0"
                               class="form-control @error('variable_salary') is-invalid @enderror"
                               value="{{ old('variable_salary', $employee->variable_salary) }}" required>
                    </div>
                </div>
                <div class="col-md-4 d-flex align-items-end pb-1">
                    <div>
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" name="ot_enabled" value="1" id="otEnabled"
                                   {{ old('ot_enabled', $employee->ot_enabled) ? 'checked' : '' }}>
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
                <button type="submit" class="btn btn-primary"><i class="fa fa-save me-1"></i>Update Employee</button>
                <a href="{{ route('employees.index') }}" class="btn btn-light">Cancel</a>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
function handleRemovePhoto(cb) {
    var $prev = $('#photoPreview');
    if (cb.checked) {
        // Grey out the current photo to signal it will be removed
        if ($prev.is('img')) {
            $prev.css({'opacity': '0.3', 'filter': 'grayscale(100%)'});
        } else {
            $prev.css({'opacity': '0.3'});
        }
        // Disable the file input while remove is ticked
        $('input[name="photo"]').prop('disabled', true);
    } else {
        $prev.css({'opacity': '', 'filter': ''});
        $('input[name="photo"]').prop('disabled', false);
    }
}

function previewEditPhoto(input) {
    if (input.files && input.files[0]) {
        var reader = new FileReader();
        reader.onload = function(e) {
            var $prev = $('#photoPreview');
            if ($prev.is('img')) {
                $prev.attr('src', e.target.result);
            } else {
                // Replace avatar div with img
                $prev.replaceWith('<img id="photoPreview" src="' + e.target.result + '" alt="Photo" class="rounded" style="width:72px;height:72px;object-fit:cover;border:2px solid #e2e8f0">');
            }
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
