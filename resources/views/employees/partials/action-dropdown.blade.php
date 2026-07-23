<div class="dropdown">
    <button class="btn btn-primary btn-sm dropdown-toggle" type="button" data-bs-toggle="dropdown">
        Options
    </button>
    <ul class="dropdown-menu dropdown-menu-end">
        <li>
            <a class="dropdown-item" href="{{ route('employees.show', $employee) }}">
                <i class="fa fa-user me-2 text-secondary"></i>View Profile
            </a>
        </li>
        <li>
            <a class="dropdown-item" href="{{ route('employees.edit', $employee) }}">
                <i class="fa fa-pen-to-square me-2 text-primary"></i>Edit Employee
            </a>
        </li>
        <li>
            <button class="dropdown-item text-danger btn-delete-employee"
                    data-url="{{ route('employees.destroy', $employee) }}">
                <i class="fa fa-trash me-2"></i>Delete Employee
            </button>
        </li>
        <li><hr class="dropdown-divider"></li>
        <li>
            @if($employee->offerLetter)
                <a class="dropdown-item" href="{{ route('offer-letters.show', $employee->offerLetter) }}">
                    <i class="fa fa-envelope-open-text me-2 text-info"></i>View Offer Letter
                </a>
            @else
                <a class="dropdown-item" href="{{ route('offer-letters.create', ['employee' => $employee->id]) }}">
                    <i class="fa fa-plus me-2 text-success"></i>Create Offer Letter
                </a>
            @endif
        </li>
        <li>
            @if($employee->confirmationLetter)
                <a class="dropdown-item" href="{{ route('confirmation-letters.show', $employee->confirmationLetter) }}">
                    <i class="fa fa-check-circle me-2 text-info"></i>View Confirmation Letter
                </a>
            @else
                <a class="dropdown-item" href="{{ route('confirmation-letters.create', ['employee' => $employee->id]) }}">
                    <i class="fa fa-plus me-2 text-success"></i>Create Confirmation Letter
                </a>
            @endif
        </li>
        <li>
            @if($employee->salarySlips->count() > 0)
                <a class="dropdown-item" href="{{ route('salary-slips.show', $employee->salarySlips->first()) }}">
                    <i class="fa fa-money-bill-wave me-2 text-info"></i>View Salary Slip
                </a>
            @else
                <a class="dropdown-item" href="{{ route('salary-slips.create', ['employee' => $employee->id]) }}">
                    <i class="fa fa-plus me-2 text-success"></i>Create Salary Slip
                </a>
            @endif
        </li>
        <li>
            @if($employee->incrementLetters->count() > 0)
                <a class="dropdown-item" href="{{ route('increment-letters.show', $employee->incrementLetters->first()) }}">
                    <i class="fa fa-arrow-trend-up me-2 text-info"></i>View Increment Letter
                </a>
            @else
                <a class="dropdown-item" href="{{ route('increment-letters.create', ['employee' => $employee->id]) }}">
                    <i class="fa fa-plus me-2 text-success"></i>Create Increment Letter
                </a>
            @endif
        </li>
    </ul>
</div>
