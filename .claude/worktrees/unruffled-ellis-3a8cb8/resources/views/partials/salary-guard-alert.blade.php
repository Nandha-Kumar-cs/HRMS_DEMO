{{-- Salary guard error (fired by HasSalaryGuard trait on store()) --}}
@if(session('salary_error'))
<div class="alert alert-danger alert-dismissible fade show mb-3" role="alert">
    <i class="fa fa-triangle-exclamation me-2"></i>
    {{ session('salary_error') }}
    <div class="mt-2">
        <a href="{{ isset($selected) && $selected ? route('employees.edit', $selected) : '#' }}"
           class="btn btn-sm btn-danger">
            <i class="fa fa-pen-to-square me-1"></i>Update Salary Now
        </a>
    </div>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif

{{-- Live salary warning when employee is selected from dropdown --}}
<div id="salaryWarningBanner" class="alert alert-warning d-none mb-3" role="alert">
    <i class="fa fa-triangle-exclamation me-2"></i>
    <strong id="salaryWarningName"></strong> has <strong>no salary configured</strong> (Fixed &amp; Variable are both ₹0).
    <a id="salaryWarningLink" href="#" class="alert-link ms-2">Update salary →</a>
</div>
