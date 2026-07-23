@extends('layouts.app')
@section('title', 'Employee Profile')
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('employees.index') }}" class="text-decoration-none">Employees</a></li>
    <li class="breadcrumb-item active">{{ $employee->full_name }}</li>
@endsection

@push('styles')
<style>
.btn-xs { padding:.2rem .5rem; font-size:.75rem; }
.tab-content { padding-top:1rem; }
.profile-avatar { width:80px;height:80px;border-radius:50%;background:#3b82f6;color:#fff;font-size:2rem;display:flex;align-items:center;justify-content:center;font-weight:700; }
.profile-photo  { width:80px;height:80px;border-radius:50%;object-fit:cover;border:3px solid #e2e8f0; }
</style>
@endpush

@section('content')
{{-- Header --}}
<div class="card page-card mb-3">
    <div class="card-body py-3">
        <div class="d-flex align-items-center gap-3">
            <a href="{{ route('employees.index') }}" class="btn btn-sm btn-outline-secondary"><i class="fa fa-arrow-left"></i></a>
            @if($employee->photo_path)
                <img src="{{ asset('storage/' . $employee->photo_path) }}" alt="{{ $employee->full_name }}" class="profile-photo">
            @else
                <div class="profile-avatar">{{ strtoupper(substr($employee->full_name,0,1)) }}</div>
            @endif
            <div>
                <h5 class="mb-0 fw-bold">{{ $employee->full_name }}</h5>
                <small class="text-muted">{{ $employee->employee_code }} &bull; {{ $employee->designation?->name ?? 'N/A' }} &bull; {{ $employee->department?->name ?? 'N/A' }}</small><br>
                <span class="badge bg-{{ $employee->status === 'active' ? 'success' : ($employee->status === 'inactive' ? 'danger' : 'warning') }}">{{ ucfirst(str_replace('_',' ',$employee->status)) }}</span>
                @if($employee->entity) <span class="badge bg-info ms-1">{{ $employee->entity->name }}</span> @endif
            </div>
            <div class="ms-auto d-flex gap-2">
                <a href="{{ route('employees.edit', $employee) }}" class="btn btn-sm btn-primary"><i class="fa fa-edit me-1"></i>Edit</a>
            </div>
        </div>
    </div>
</div>

{{-- Tabs --}}
<ul class="nav nav-tabs mb-0 px-1" id="profileTabs">
    <li class="nav-item"><a class="nav-link active" data-bs-toggle="tab" href="#overview">Overview</a></li>
    <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#family">Family</a></li>
    <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#benefits">Benefits</a></li>
    <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#bonuses">Bonuses</a></li>
    <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#increments">Increments</a></li>
    <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#promotions">Promotions</a></li>
    <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#loans">Loans & Advances</a></li>
    <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#documents">Documents</a></li>
    <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#bank"><i class="fas fa-university me-1"></i>Bank Details</a></li>
    <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#assets">Assets</a></li>
    <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#payroll">Salary History</a></li>
</ul>

<div class="card page-card border-top-0" style="border-radius:0 0 .5rem .5rem">
<div class="card-body">
<div class="tab-content">

    {{-- ── OVERVIEW ── --}}
    <div class="tab-pane fade show active" id="overview">
        <div class="row g-3">
            <div class="col-md-6">
                <h6 class="text-primary fw-semibold border-bottom pb-2">Personal Information</h6>
                <table class="table table-sm table-borderless">
                    <tr><th class="text-muted" style="width:160px">Email</th><td>{{ $employee->email }}</td></tr>
                    <tr><th class="text-muted">Phone</th><td>{{ $employee->phone }}</td></tr>
                    <tr><th class="text-muted">Date of Birth</th><td>{{ $employee->dob?->format('d M Y') ?? '-' }}</td></tr>
                    <tr><th class="text-muted">Gender</th><td>{{ ucfirst($employee->gender ?? '-') }}</td></tr>
                </table>
            </div>
            <div class="col-md-6">
                <h6 class="text-primary fw-semibold border-bottom pb-2">Employment Details</h6>
                <table class="table table-sm table-borderless">
                    <tr><th class="text-muted" style="width:160px">Joining Date</th><td>{{ $employee->joining_date?->format('d M Y') ?? '-' }}</td></tr>
                    <tr><th class="text-muted">Probation End</th><td>{{ $employee->probation_end?->format('d M Y') ?? '-' }}</td></tr>
                    <tr><th class="text-muted">Reporting Manager</th><td>{{ $employee->reportingManager?->full_name ?? '-' }}</td></tr>
                    <tr><th class="text-muted">Entity</th><td>{{ $employee->entity?->name ?? '-' }}</td></tr>
                </table>
            </div>
            <div class="col-md-6">
                <h6 class="text-primary fw-semibold border-bottom pb-2">Salary</h6>
                <table class="table table-sm table-borderless">
                    <tr><th class="text-muted" style="width:160px">CTC per Month</th><td>₹{{ number_format($employee->fixed_salary,2) }}</td></tr>
                    <tr><th class="text-muted">Variable / Perf. Pay</th><td>₹{{ number_format($employee->variable_salary,2) }}</td></tr>
                    <tr><th class="text-muted">CTC</th><td><strong class="text-primary">₹{{ number_format($employee->ctc,2) }}</strong></td></tr>
                </table>
            </div>
            <div class="col-md-6">
                <h6 class="text-primary fw-semibold border-bottom pb-2">Quick Stats</h6>
                <div class="row g-2 text-center">
                    <div class="col-4"><div class="bg-light rounded p-2"><div class="fw-bold text-primary">{{ $employee->increments->count() }}</div><small class="text-muted">Increments</small></div></div>
                    <div class="col-4"><div class="bg-light rounded p-2"><div class="fw-bold text-success">{{ $employee->promotions->count() }}</div><small class="text-muted">Promotions</small></div></div>
                    <div class="col-4"><div class="bg-light rounded p-2"><div class="fw-bold text-warning">{{ $employee->loans->where('status','active')->count() }}</div><small class="text-muted">Active Loans</small></div></div>
                    <div class="col-4"><div class="bg-light rounded p-2"><div class="fw-bold text-info">{{ $employee->documents->count() }}</div><small class="text-muted">Documents</small></div></div>
                    <div class="col-4"><div class="bg-light rounded p-2"><div class="fw-bold text-secondary">{{ $employee->currentAssets->count() }}</div><small class="text-muted">Assets</small></div></div>
                    <div class="col-4"><div class="bg-light rounded p-2"><div class="fw-bold text-dark">{{ $employee->salarySlips->count() }}</div><small class="text-muted">Slips</small></div></div>
                </div>
            </div>
        </div>
    </div>

    {{-- ── FAMILY MEMBERS ── --}}
    <div class="tab-pane fade" id="family">
        <div class="d-flex justify-content-between mb-3">
            <h6 class="fw-semibold mb-0"><i class="fa fa-people-roof me-1 text-primary"></i>Family Details</h6>
            @if(in_array(auth()->user()->role ?? 'admin', ['admin','manager']))
            <button class="btn btn-sm btn-primary" id="btnAddFamily"><i class="fa fa-plus me-1"></i>Add Member</button>
            @endif
        </div>
        @if($employee->familyMembers->isEmpty())
            <p class="text-muted text-center py-4">No family members recorded.</p>
        @else
        <div class="table-responsive">
            <table class="table table-sm table-hover table-bordered align-middle">
                <thead class="table-dark">
                    <tr><th>#</th><th>Name</th><th>Relationship</th><th>DOB</th><th>Age</th><th>Occupation</th><th>Contact</th><th>Dependency</th><th></th></tr>
                </thead>
                <tbody>
                    @foreach($employee->familyMembers as $i => $fm)
                    <tr>
                        <td>{{ $i + 1 }}</td>
                        <td class="fw-semibold">{{ $fm->name }}</td>
                        <td><span class="badge bg-light text-dark">{{ $fm->relationship }}</span></td>
                        <td>{{ $fm->dob?->format('d M Y') ?? '—' }}</td>
                        <td>{{ $fm->age ?? '—' }}</td>
                        <td>{{ $fm->occupation ?? '—' }}</td>
                        <td>{{ $fm->contact_number ?? '—' }}</td>
                        <td>
                            <span class="badge bg-{{ $fm->dependency_status === 'dependent' ? 'info' : 'secondary' }}">
                                {{ ucfirst($fm->dependency_status) }}
                            </span>
                        </td>
                        <td>
                            @if(in_array(auth()->user()->role ?? 'admin', ['admin','manager']))
                            <div class="d-flex gap-1">
                                <button class="btn btn-xs btn-outline-primary btn-edit-family"
                                        data-id="{{ $fm->id }}"
                                        data-url="{{ route('family-members.show', $fm) }}"
                                        data-update="{{ route('family-members.update', $fm) }}"
                                        title="Edit">
                                    <i class="fa fa-pen-to-square"></i>
                                </button>
                                <form action="{{ route('family-members.destroy', $fm) }}" method="POST" class="d-inline" onsubmit="return confirm('Remove this family member?')">
                                    @csrf @method('DELETE')
                                    <button class="btn btn-xs btn-outline-danger" title="Delete"><i class="fa fa-trash"></i></button>
                                </form>
                            </div>
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @endif

        @if(in_array(auth()->user()->role ?? 'admin', ['admin','manager']))
        {{-- Add Family Member Modal --}}
        <div class="modal fade" id="familyModal" tabindex="-1">
            <div class="modal-dialog modal-lg">
                <form action="{{ route('family-members.store', $employee) }}" method="POST">
                    @csrf
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title fw-semibold">Add Family Member</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold">Name <span class="text-danger">*</span></label>
                                    <input type="text" name="name" class="form-control" maxlength="150" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold">Relationship <span class="text-danger">*</span></label>
                                    <select name="relationship" class="form-select" required>
                                        <option value="">Select</option>
                                        <option>Father</option><option>Mother</option><option>Spouse</option>
                                        <option>Son</option><option>Daughter</option><option>Brother</option>
                                        <option>Sister</option><option>Guardian</option><option>Other</option>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label fw-semibold">Date of Birth</label>
                                    <input type="date" name="dob" class="form-control">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label fw-semibold">Occupation</label>
                                    <input type="text" name="occupation" class="form-control" maxlength="100">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label fw-semibold">Contact Number</label>
                                    <input type="text" name="contact_number" class="form-control" maxlength="30">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold">Dependency Status <span class="text-danger">*</span></label>
                                    <select name="dependency_status" class="form-select" required>
                                        <option value="dependent">Dependent</option>
                                        <option value="independent">Independent</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button class="btn btn-primary"><i class="fa fa-save me-1"></i>Save</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
        {{-- Edit Family Member Modal --}}
        <div class="modal fade" id="editFamilyModal" tabindex="-1">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title fw-semibold">Edit Family Member</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Name <span class="text-danger">*</span></label>
                                <input type="text" id="efm_name" class="form-control" maxlength="150" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Relationship <span class="text-danger">*</span></label>
                                <select id="efm_relationship" class="form-select" required>
                                    <option value="">Select</option>
                                    <option>Father</option><option>Mother</option><option>Spouse</option>
                                    <option>Son</option><option>Daughter</option><option>Brother</option>
                                    <option>Sister</option><option>Guardian</option><option>Other</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-semibold">Date of Birth</label>
                                <input type="date" id="efm_dob" class="form-control">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-semibold">Occupation</label>
                                <input type="text" id="efm_occupation" class="form-control" maxlength="100">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-semibold">Contact Number</label>
                                <input type="text" id="efm_contact" class="form-control" maxlength="30">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Dependency Status <span class="text-danger">*</span></label>
                                <select id="efm_dependency" class="form-select" required>
                                    <option value="dependent">Dependent</option>
                                    <option value="independent">Independent</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button id="btnSaveEditFamily" class="btn btn-primary"><i class="fa fa-save me-1"></i>Update</button>
                    </div>
                </div>
            </div>
        </div>

        @push('scripts')
        <script>
        $(function(){
            var familyModal     = new bootstrap.Modal(document.getElementById('familyModal'));
            var editFamilyModal = new bootstrap.Modal(document.getElementById('editFamilyModal'));
            var editUpdateUrl   = '';

            $('#btnAddFamily').on('click', function(){ familyModal.show(); });

            // Open edit modal and populate fields
            $(document).on('click', '.btn-edit-family', function(){
                var fetchUrl  = $(this).data('url');
                editUpdateUrl = $(this).data('update');

                $.getJSON(fetchUrl, function(fm){
                    $('#efm_name').val(fm.name);
                    $('#efm_relationship').val(fm.relationship);
                    $('#efm_dob').val(fm.dob ? fm.dob.substring(0,10) : '');
                    $('#efm_occupation').val(fm.occupation ?? '');
                    $('#efm_contact').val(fm.contact_number ?? '');
                    $('#efm_dependency').val(fm.dependency_status);
                    editFamilyModal.show();
                });
            });

            // Save edit via AJAX PUT
            $('#btnSaveEditFamily').on('click', function(){
                var btn = $(this).prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span>Saving…');
                $.ajax({
                    url:  editUpdateUrl,
                    type: 'PUT',
                    headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' },
                    data: {
                        name:               $('#efm_name').val(),
                        relationship:       $('#efm_relationship').val(),
                        dob:                $('#efm_dob').val() || null,
                        occupation:         $('#efm_occupation').val() || null,
                        contact_number:     $('#efm_contact').val() || null,
                        dependency_status:  $('#efm_dependency').val(),
                    },
                    success: function(res){
                        editFamilyModal.hide();
                        location.reload();
                    },
                    error: function(xhr){
                        btn.prop('disabled', false).html('<i class="fa fa-save me-1"></i>Update');
                        var msg = xhr.responseJSON?.message ?? 'Update failed. Please try again.';
                        alert(msg);
                    }
                });
            });
        });
        </script>
        @endpush
        @endif
    </div>

    {{-- ── BENEFITS ── --}}
    <div class="tab-pane fade" id="benefits">
        <div class="d-flex justify-content-between mb-3">
            <h6 class="fw-semibold mb-0"><i class="fa fa-gift me-1 text-primary"></i>Benefit Funds</h6>
            @if(in_array(auth()->user()->role ?? 'admin', ['admin','manager']))
            <a href="{{ route('employee-benefits.create', ['employee_id' => $employee->id]) }}" class="btn btn-sm btn-primary"><i class="fa fa-plus me-1"></i>Assign Benefit</a>
            @endif
        </div>
        @if($employee->benefits->isEmpty())
            <p class="text-muted text-center py-4">No benefits assigned.</p>
        @else
        <div class="table-responsive">
            <table class="table table-sm table-hover table-bordered align-middle">
                <thead class="table-dark"><tr><th>#</th><th>Fund Type</th><th>Amount</th><th>Effective From</th><th>Status</th><th>Notes</th></tr></thead>
                <tbody>
                    @foreach($employee->benefits as $i => $b)
                    <tr>
                        <td>{{ $i + 1 }}</td>
                        <td><span class="badge bg-{{ $b->fundType?->color ?? 'secondary' }}">{{ $b->fundType?->name ?? '—' }}</span></td>
                        <td class="text-success fw-semibold">₹{{ number_format($b->amount, 2) }}</td>
                        <td>{{ $b->effective_month?->format('M Y') }}</td>
                        <td><span class="badge bg-{{ $b->status === 'active' ? 'success' : 'secondary' }}">{{ ucfirst($b->status) }}</span></td>
                        <td class="small text-muted">{{ $b->description ?: '—' }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @endif
    </div>

    {{-- ── BONUSES ── --}}
    <div class="tab-pane fade" id="bonuses">
        <div class="d-flex justify-content-between mb-3">
            <h6 class="fw-semibold mb-0"><i class="fa fa-trophy me-1 text-warning"></i>Bonuses & Incentives</h6>
            @if(in_array(auth()->user()->role ?? 'admin', ['admin','manager']))
            <a href="{{ route('employee-bonuses.create', ['employee_id' => $employee->id]) }}" class="btn btn-sm btn-primary"><i class="fa fa-plus me-1"></i>Add Bonus</a>
            @endif
        </div>
        @if($employee->bonuses->isEmpty())
            <p class="text-muted text-center py-4">No bonus / incentive records.</p>
        @else
        <div class="table-responsive">
            <table class="table table-sm table-hover table-bordered align-middle">
                <thead class="table-dark"><tr><th>#</th><th>Type</th><th>Amount</th><th>Reason</th><th>Payroll Month</th><th>Status</th></tr></thead>
                <tbody>
                    @foreach($employee->bonuses as $i => $b)
                    <tr>
                        <td>{{ $i + 1 }}</td>
                        <td><span class="badge bg-{{ $b->type_color }}">{{ $b->type_label }}</span></td>
                        <td class="text-success fw-semibold">₹{{ number_format($b->amount, 2) }}</td>
                        <td class="small">{{ $b->reason }}</td>
                        <td>{{ date('M Y', mktime(0,0,0,$b->payroll_month,1,$b->payroll_year)) }}</td>
                        <td>
                            @php $sBadge = ['pending'=>'warning text-dark','approved'=>'success','rejected'=>'danger'][$b->status] ?? 'secondary'; @endphp
                            <span class="badge bg-{{ $sBadge }}">{{ ucfirst($b->status) }}</span>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @endif
    </div>

    {{-- ── INCREMENTS ── --}}
    <div class="tab-pane fade" id="increments">
        <div class="d-flex justify-content-between mb-3">
            <h6 class="fw-semibold mb-0">Increment History</h6>
            <a href="{{ route('increments.create', ['employee' => $employee->id]) }}" class="btn btn-sm btn-primary"><i class="fa fa-plus me-1"></i>Add Increment</a>
        </div>
        @if($employee->increments->isEmpty())
            <p class="text-muted text-center py-4">No increment records found.</p>
        @else
        <div class="table-responsive">
            <table class="table table-sm table-hover table-bordered align-middle">
                <thead class="table-dark"><tr><th>#</th><th>Effective Date</th><th>Previous Salary</th><th>New Salary</th><th>Increment</th><th>%</th><th>Remarks</th><th></th></tr></thead>
                <tbody>
                    @foreach($employee->increments as $i => $inc)
                    <tr>
                        <td>{{ $i+1 }}</td>
                        <td>{{ $inc->effective_date->format('d M Y') }}</td>
                        <td>₹{{ number_format($inc->previous_salary,2) }}</td>
                        <td>₹{{ number_format($inc->new_salary,2) }}</td>
                        <td class="text-success">+₹{{ number_format($inc->increment_amount,2) }}</td>
                        <td><span class="badge bg-success">{{ $inc->increment_percentage }}%</span></td>
                        <td>{{ $inc->remarks ?? '-' }}</td>
                        <td>
                            <a href="{{ route('increments.edit', $inc) }}" class="btn btn-xs btn-outline-primary"><i class="fa fa-edit"></i></a>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @endif
    </div>

    {{-- ── PROMOTIONS ── --}}
    <div class="tab-pane fade" id="promotions">
        <div class="d-flex justify-content-between mb-3">
            <h6 class="fw-semibold mb-0">Promotion History</h6>
            <a href="{{ route('promotions.create', ['employee' => $employee->id]) }}" class="btn btn-sm btn-primary"><i class="fa fa-plus me-1"></i>Add Promotion</a>
        </div>
        @if($employee->promotions->isEmpty())
            <p class="text-muted text-center py-4">No promotion records found.</p>
        @else
        <div class="table-responsive">
            <table class="table table-sm table-hover table-bordered align-middle">
                <thead class="table-dark"><tr><th>#</th><th>Effective Date</th><th>Previous Designation</th><th>New Designation</th><th>Department</th><th>Remarks</th><th></th></tr></thead>
                <tbody>
                    @foreach($employee->promotions as $i => $promo)
                    <tr>
                        <td>{{ $i+1 }}</td>
                        <td>{{ $promo->effective_date->format('d M Y') }}</td>
                        <td>{{ $promo->previousDesignation?->name ?? '-' }}</td>
                        <td><strong>{{ $promo->newDesignation?->name ?? '-' }}</strong></td>
                        <td>{{ $promo->department?->name ?? '-' }}</td>
                        <td>{{ $promo->remarks ?? '-' }}</td>
                        <td>
                            <a href="{{ route('promotions.edit', $promo) }}" class="btn btn-xs btn-outline-primary"><i class="fa fa-edit"></i></a>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @endif
    </div>

    {{-- ── LOANS ── --}}
    <div class="tab-pane fade" id="loans">
        <div class="d-flex justify-content-between mb-3">
            <h6 class="fw-semibold mb-0">Loan & Advance History</h6>
            <a href="{{ route('loans.create', ['employee' => $employee->id]) }}" class="btn btn-sm btn-primary"><i class="fa fa-plus me-1"></i>Add Loan / Advance</a>
        </div>
        @if($employee->loans->isEmpty())
            <p class="text-muted text-center py-4">No loan records found.</p>
        @else
        <div class="table-responsive">
            <table class="table table-sm table-hover table-bordered align-middle">
                <thead class="table-dark">
                    <tr>
                        <th>Type</th>
                        <th>Principal</th>
                        <th>Interest</th>
                        <th>Total Due</th>
                        <th>Date</th>
                        <th>Monthly EMI</th>
                        <th>Repaid</th>
                        <th>Pending</th>
                        <th>Status</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($employee->loans as $loan)
                    <tr>
                        <td><span class="badge bg-{{ $loan->type === 'loan' ? 'primary' : 'info' }}">{{ ucfirst($loan->type) }}</span></td>
                        <td>₹{{ number_format($loan->amount, 2) }}</td>
                        <td>
                            @if($loan->total_interest > 0)
                                <span class="text-warning">₹{{ number_format($loan->total_interest, 2) }}</span>
                                <small class="text-muted d-block" style="font-size:.7rem">{{ $loan->interest_rate }}% / yr</small>
                            @else
                                <span class="text-muted">—</span>
                            @endif
                        </td>
                        <td><strong>₹{{ number_format($loan->total_due, 2) }}</strong></td>
                        <td>{{ $loan->date_given->format('d M Y') }}</td>
                        <td>
                            ₹{{ number_format($loan->monthly_deduction, 2) }}
                            @if($loan->total_interest > 0)
                                <small class="text-muted d-block" style="font-size:.7rem">incl. interest</small>
                            @endif
                        </td>
                        <td class="text-success fw-semibold">₹{{ number_format($loan->returned_amount, 2) }}</td>
                        <td>
                            @if($loan->pending_amount > 0)
                                <span class="text-danger fw-semibold">₹{{ number_format($loan->pending_amount, 2) }}</span>
                            @else
                                <span class="text-success">₹0.00</span>
                            @endif
                        </td>
                        <td><span class="badge bg-{{ $loan->status === 'active' ? 'success' : 'secondary' }}">{{ ucfirst($loan->status) }}</span></td>
                        <td><a href="{{ route('loans.show', $loan) }}" class="btn btn-xs btn-outline-info"><i class="fa fa-eye"></i></a></td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @endif
    </div>

    {{-- ── DOCUMENTS ── --}}
    <div class="tab-pane fade" id="documents">
        <div class="d-flex justify-content-between mb-3">
            <h6 class="fw-semibold mb-0">Documents</h6>
            <a href="{{ route('employee-documents.index', ['employee' => $employee->id]) }}" class="btn btn-sm btn-primary"><i class="fa fa-upload me-1"></i>Upload Document</a>
        </div>
        @if($employee->documents->isEmpty())
            <p class="text-muted text-center py-4">No documents uploaded yet.</p>
        @else
        <div class="table-responsive">
            <table class="table table-sm table-hover table-bordered align-middle">
                <thead class="table-dark"><tr><th>Type</th><th>Name</th><th>Size</th><th>Uploaded</th><th>Actions</th></tr></thead>
                <tbody>
                    @foreach($employee->documents as $doc)
                    <tr>
                        <td><span class="badge bg-secondary">{{ $doc->type_label }}</span></td>
                        <td>{{ $doc->document_name }}</td>
                        <td>{{ $doc->file_size_human }}</td>
                        <td>{{ $doc->created_at->format('d M Y') }}</td>
                        <td class="text-nowrap">
                            <a href="{{ route('employee-documents.preview', $doc) }}" class="btn btn-xs btn-outline-info" target="_blank" title="View document">
                                <i class="fa fa-eye"></i>
                            </a>
                            <a href="{{ route('employee-documents.download', $doc) }}" class="btn btn-xs btn-outline-primary ms-1" title="Download">
                                <i class="fa fa-download"></i>
                            </a>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @endif
    </div>

    {{-- ── BANK DETAILS ── --}}
    <div class="tab-pane fade" id="bank">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h6 class="fw-semibold mb-0"><i class="fas fa-university me-1 text-primary"></i>Bank Details</h6>
            @if(in_array(auth()->user()->role ?? 'admin', ['admin','manager']))
            <a href="{{ route('employee-bank-details.edit', $employee) }}" class="btn btn-sm btn-primary">
                <i class="fa {{ $employee->bankDetail ? 'fa-edit' : 'fa-plus' }} me-1"></i>
                {{ $employee->bankDetail ? 'Edit' : 'Add' }} Bank Details
            </a>
            @endif
        </div>

        @if(!$employee->bankDetail)
            <p class="text-muted text-center py-4"><i class="fas fa-university fa-2x d-block mb-2 text-muted"></i>No bank details recorded yet.</p>
        @else
        @php $bd = $employee->bankDetail; @endphp
        <div class="row g-3">
            <div class="col-md-6">
                <div class="card border-0 bg-light h-100">
                    <div class="card-body">
                        <h6 class="text-primary fw-semibold border-bottom pb-2 mb-3">Account Information</h6>
                        <table class="table table-sm table-borderless mb-0">
                            <tr><th class="text-muted" style="width:180px">Bank Name</th><td class="fw-semibold">{{ $bd->bank_name }}</td></tr>
                            <tr><th class="text-muted">Account Holder</th><td class="fw-semibold">{{ $bd->account_holder_name }}</td></tr>
                            <tr><th class="text-muted">Account Number</th>
                                <td>
                                    <span class="fw-semibold font-monospace" id="accNumDisplay">
                                        {{ str_repeat('•', strlen($bd->account_number) - 4) . substr($bd->account_number, -4) }}
                                    </span>
                                    <button class="btn btn-xs btn-outline-secondary ms-2" id="toggleAccNum" title="Show/Hide">
                                        <i class="fa fa-eye" id="accNumIcon"></i>
                                    </button>
                                    <span class="d-none" id="accNumFull">{{ $bd->account_number }}</span>
                                </td>
                            </tr>
                        </table>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card border-0 bg-light h-100">
                    <div class="card-body">
                        <h6 class="text-primary fw-semibold border-bottom pb-2 mb-3">Branch Information</h6>
                        <table class="table table-sm table-borderless mb-0">
                            <tr><th class="text-muted" style="width:180px">IFSC Code</th><td class="fw-semibold font-monospace">{{ $bd->ifsc_code }}</td></tr>
                            <tr><th class="text-muted">Branch Name</th><td>{{ $bd->branch_name }}</td></tr>
                            <tr><th class="text-muted">UPI ID</th><td>{{ $bd->upi_id ?: '—' }}</td></tr>
                        </table>
                    </div>
                </div>
            </div>
            <div class="col-12">
                <small class="text-muted"><i class="fa fa-shield-alt me-1"></i>Last updated: {{ $bd->updated_at->format('d M Y, h:i A') }}</small>
            </div>
        </div>
        @endif
    </div>

    {{-- ── ASSETS ── --}}
    <div class="tab-pane fade" id="assets">
        <h6 class="fw-semibold mb-3">Assigned Assets</h6>
        @if($employee->assetAssignments->isEmpty())
            <p class="text-muted text-center py-4">No assets assigned.</p>
        @else
        <div class="table-responsive">
            <table class="table table-sm table-hover table-bordered align-middle">
                <thead class="table-dark"><tr><th>Asset</th><th>Type</th><th>Serial No.</th><th>Issued</th><th>Returned</th><th>Condition</th></tr></thead>
                <tbody>
                    @foreach($employee->assetAssignments as $assign)
                    <tr>
                        <td>{{ $assign->asset->asset_name }}</td>
                        <td>{{ $assign->asset->type_label }}</td>
                        <td>{{ $assign->asset->serial_number ?? '-' }}</td>
                        <td>{{ $assign->issue_date->format('d M Y') }}</td>
                        <td>
                            @if($assign->return_date)
                                {{ $assign->return_date->format('d M Y') }}
                            @else
                                <span class="badge bg-warning text-dark">In Hand</span>
                            @endif
                        </td>
                        <td>{{ $assign->condition_on_issue ?? '-' }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @endif
    </div>

    {{-- ── PAYROLL ── --}}
    <div class="tab-pane fade" id="payroll">
        <div class="d-flex justify-content-between mb-3">
            <h6 class="fw-semibold mb-0">Salary Slip History</h6>
            <a href="{{ route('salary-slips.create', ['employee' => $employee->id]) }}" class="btn btn-sm btn-primary"><i class="fa fa-plus me-1"></i>Generate Slip</a>
        </div>
        @if($employee->salarySlips->isEmpty())
            <p class="text-muted text-center py-4">No salary slips generated.</p>
        @else
        <div class="table-responsive">
            <table class="table table-sm table-hover table-bordered align-middle">
                <thead class="table-dark"><tr><th>Month / Year</th><th>CTC / Month</th><th>Net Salary</th><th></th></tr></thead>
                <tbody>
                    @foreach($employee->salarySlips->sortByDesc(fn($s) => $s->year * 100 + $s->month) as $slip)
                    <tr>
                        <td>{{ $slip->month_name }} {{ $slip->year }}</td>
                        <td>₹{{ number_format($slip->fixed_salary,2) }}</td>
                        <td><strong class="text-primary">₹{{ number_format($slip->net_salary,2) }}</strong></td>
                        <td>
                            <a href="{{ route('salary-slips.show', $slip) }}" class="btn btn-xs btn-outline-primary"><i class="fa fa-eye"></i></a>
                            <a href="{{ route('salary-slips.pdf', $slip) }}" class="btn btn-xs btn-outline-danger" target="_blank"><i class="fa fa-file-pdf"></i></a>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @endif
    </div>

</div>
</div>
</div>


@endsection

@push('scripts')
<script>
$(function () {
    // ── Tab persistence: URL hash + server-side session ──────────────────────
    // Priority: 1) Server session (after form submit), 2) URL hash (F5 refresh)
    var serverTab  = '{{ session('active_tab') }}';
    var hashTab    = window.location.hash ? window.location.hash.substring(1) : null;
    var targetTab  = serverTab || hashTab;

    if (targetTab) {
        var $tab = $('#profileTabs a[href="#' + targetTab + '"]');
        if ($tab.length) {
            // Deactivate current active tab first
            $('#profileTabs .nav-link.active').removeClass('active').attr('aria-expanded', false);
            $('.tab-pane.show.active').removeClass('show active');
            // Activate the target tab
            $tab.addClass('active');
            $('#' + targetTab).addClass('show active');
        }
    }

    // Update URL hash when any tab is clicked so F5 stays on same tab
    $('#profileTabs a[data-bs-toggle="tab"]').on('shown.bs.tab', function (e) {
        var id = $(e.target).attr('href').substring(1);
        history.replaceState(null, null, '#' + id);
    });

    // Auto-dismiss toast after 3 s
    setTimeout(function () { $('.toast').fadeOut(400); }, 3000);

    // ── Bank Details — toggle account number visibility ──────────────────
    var accNumVisible = false;
    $('#toggleAccNum').on('click', function () {
        accNumVisible = !accNumVisible;
        var masked = '{{ str_repeat("•", max(0, strlen($employee->bankDetail?->account_number ?? "") - 4)) }}' +
                     '{{ substr($employee->bankDetail?->account_number ?? "", -4) }}';
        var full   = $('#accNumFull').text();
        $('#accNumDisplay').text(accNumVisible ? full : masked);
        $('#accNumIcon').toggleClass('fa-eye', !accNumVisible).toggleClass('fa-eye-slash', accNumVisible);
    });
});
</script>
@endpush
